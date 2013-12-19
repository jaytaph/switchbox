<?php

namespace SwitchBox;

use SwitchBox\DHT\KeyPair;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;
use SwitchBox\Iface\iSockHandler;
use SwitchBox\Packet\Line\Stream;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\Line\Processor\Peer as LinePeer;
use SwitchBox\Packet\Line\Processor\Seek as LineSeek;
use SwitchBox\Packet\Ping;

// Make sure we are using GMP extension for AES libraries
if (! defined('USE_EXT')) {
    define('USE_EXT', 'GMP');
}

// Some default defines for coloring output
define('ANSI_RESET',   "\x1b[0m");
define('ANSI_RED',     "\x1b[31;1m");
define('ANSI_GREEN',   "\x1b[32;1m");
define('ANSI_YELLOW',  "\x1b[33;1m");
define('ANSI_BLUE',    "\x1b[34;1m");
define('ANSI_MAGENTA', "\x1b[35;1m");
define('ANSI_CYAN',    "\x1b[36;1m");
define('ANSI_WHITE',   "\x1b[37;1m");


class SwitchBox {
    const SELECT_TIMEOUT            = 5;        // Nr of seconds before socket_select() will timeout to do housekeeping
    const MAX_IDLE_STREAM_TIME      = 15;       // Nr of seconds a stream can be idle before it's closed

    /** @var \SwitchBox\DHT\KeyPair */
    protected $keypair;
    /** @var DHT\Node */
    protected $self_node;                   // Our own node
    /** @var DHT\Mesh */
    protected $mesh;                        // Actual DHT mesh

    /** @var bool */
    protected $ended = false;               // Has the application ended?

    /** @var \SwitchBox\Iface\Json */
    protected $json_interface;              // JSON TCP interface
    /** @var \SwitchBox\Iface\Admin  */
    protected $admin_interface;             // Telnet Admin interface
    /** @var \SwitchBox\Iface\Telehash */
    protected $telehash_interface;          // UDP telehash interface

    /** @var iSockHandler[] */
    protected $socket_handlers = array();


    public function __construct(array $seeds, KeyPair $keypair, $udp_port = 42424) {
        $this->start_time = time();

        // Setup generic structures
        $this->mesh = new Mesh($this);

        $this->telehash_interface = new Iface\Telehash($this, $udp_port);
        $this->addSocketHandler("telehash", $this->telehash_interface);

        // Create self node based on keypair
        $this->keypair = $keypair;
        $this->self_node = new Node(0, 0, $keypair->getPublicKey(), null);
        $this->mesh->addNode($this->self_node);

        // Add and connect seeds to the mesh
        foreach ($seeds as $seed) {
            $this->mesh->addNode($seed);
            $this->send($seed, Open::generate($this, $seed, null));
        }

        // Create our communication interfaces
        $this->admin_interface = new Iface\Admin($this, 42424);
        $this->addSocketHandler("admin panel", $this->admin_interface);

        $this->json_interface = new Iface\Json($this, 42425);
        $this->addSocketHandler("json", $this->json_interface);
    }

    /**
     * @return \SwitchBox\DHT\KeyPair
     */
    public function getKeypair()
    {
        return $this->keypair;
    }

    /**
     * @return int
     */
    public function getStartTime() {
        return $this->start_time;
    }

    /**
     * @return \SwitchBox\DHT\Mesh
     */
    public function getMesh()
    {
        return $this->mesh;
    }

    /**
     * @return Node
     */
    public function getSelfNode() {
        return $this->self_node;
    }

    /**
     * @return \SwitchBox\Iface\Telehash
     */
    public function getTelehashInterface()
    {
        return $this->telehash_interface;
    }


    public function send(Node $node, Packet $packet) {
        $th = $this->getTelehashInterface();
        return $th->send($packet->encode(), $node->getIp(), $node->getPort());
    }


    /**
     * @throws \RunTimeException
     */
    public function loop() {
        do {
            // Wait for incoming data from any socket
            $r = array();
            foreach ($this->getSocketHandlers() as $handler) {
                /** @var $handler iSockHandler */
                $r = array_merge($r, $handler->getSelectSockets());
            }
            $w = $x = NULL;
            $ret = socket_select($r, $w, $x, self::SELECT_TIMEOUT);
            if ($ret === false) {
                throw new \RunTimeException("socket_select() failed: ".socket_strerror(socket_last_error()."\n"));
            }

            // No data found, but a timeout occurred, we can do maintenance in the meantime
            if ($ret == 0) {
                $this->doMaintenance();
                continue;
            }

            // Handle socket data if any
            foreach ($r as $sock) {
                foreach ($this->getSocketHandlers() as $handler) {
                    /** @var $handler iSockHandler */
                    if ($handler->handle($this, $sock)) break;
                }
            }

        } while (! $this->ended);
    }

    /**
     * @param $type
     * @param iSockHandler $handler
     */
    public function addSocketHandler($type, iSockHandler $handler) {
        $this->socket_handlers[$type] = $handler;
    }


    /**
     * @return iSockHandler[]
     */
    public function getSocketHandlers() {
        return $this->socket_handlers;
    }


    /**
     * @param $type
     * @return iSockHandler
     */
    public function getSocketHandler($type) {
        if (isset($this->socket_handlers[$type])) {
            return $this->socket_handlers[$type];
        }
        return null;
    }

    /**
     *
     */
    public function endApp() {
        $this->ended = true;
    }


    public function doMaintenance() {
        print ANSI_CYAN . "*** Maintenance Start". ANSI_RESET . "\n";
//        $this->_seekNodes();
        $this->_connectToNodes();
        $this->_pingNodes();
//        $this->_closeIdleStreams();
        print ANSI_CYAN . "*** Maintenance End". ANSI_RESET . "\n";
    }

    protected function _seekNodes() {
        $hashes = array();
//        foreach ($this->getMesh()->getAllNodes() as $node) {
//            $hashes[] = $node->getName();
//        }
        $hashes[] = $this->getSelfNode()->getName();

        foreach ($hashes as $hash) {
            foreach ($this->getMesh()->getClosestForHash($hash) as $node) {
                $stream = new Stream($this, $node);
                $stream->addProcessor("seek", new LineSeek($stream));
                $stream->start(array('hash' => $hash));
            }
        }
    }

    protected function _pingNodes() {
        foreach ($this->getMesh()->getConnectedNodes() as $node) {
            if ($node->getHealth() == Node::HEALTH_UNKNOWN && $node->hasAddress()) {
                // Send another ping
                $stream = new Stream($this, $node);
                $stream->addProcessor("seek", new LineSeek($stream));
                $stream->start(array('hash' => $this->getSelfNode()->getName()));
                $node->addPing();
            }

        }
    }


    protected function _connectToNodes() {
        // Find all nodes that aren't connected yet
        $nodes = array();
        foreach ($this->getMesh()->getAllNodes() as $node) {
            if ($node->isConnected()) continue;
            if ($node->getName() == $this->getSelfNode()->getName()) continue;
            $nodes[] = $node;
        }

        foreach ($nodes as $node) {
            /** @var $node Node */

            // Send out a ping packet, so they might punch through our NAT (if any)
            $this->send($node, Ping::generate($this));

            // Ask (all!??) nodes to let destination connect to use
            foreach ($this->getMesh()->getConnectedNodes() as $seed) {

                // Don't ask ourselves.
                if ($seed->getName() == $this->getSelfNode()->getName()) continue;

                $stream = new Stream($this, $seed);
                $stream->addProcessor("peer", new LinePeer($stream));
                $stream->start(array('hash' => $node->getName()));
            }
        }
    }

    /**
     *
     */
    protected function _closeIdleStreams() {
        foreach ($this->getMesh()->getAllNodes() as $node) {
            foreach ($node->getStreams() as $stream) {
                // No activity since 30 seconds, we should close the stream
                if ($stream->getIdleTime() > self::MAX_IDLE_STREAM_TIME) {
                    print "Closing stream $stream \n";
                    $node->removeStream($stream);
                }
            }
        }
    }



//    public function addCustomHandler($type, $processor) {
//        $th = $this->getTelehashInterface();
//
//        $lp = $th->getPacketHandler('line');
//        if ($lp == null) {
//            throw new \UnexpectedValueException("A line processor should always be available");
//        }
//
//        $lp->addCustomStreamProcessor($type, $processor);
//    }

    /**
     * @return string
     */
    public function __toString() {
        return "SwitchBox[".$this->getSelfNode()->getName()."]";
    }

}
