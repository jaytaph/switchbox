<?php

namespace SwitchBox;

use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\Line\Peer as LinePeer;
use SwitchBox\Packet\Line\Seek as LineSeek;
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
    const SELECT_TIMEOUT        = 5;        // Nr of seconds before socket_select() will timeout to do housekeeping

    /** @var \SwitchBox\KeyPair */
    protected $keypair;
    /** @var DHT\Node */
    protected $self_node;                   // Our own node
    /** @var DHT\Mesh */
    protected $mesh;                        // Actual DHT mesh
    /** @var TxQueue */
    protected $txqueue;                     // Our transmit buffer with packets

    /** @var bool */
    protected $ended = false;               // Has the application ended?

    /** @var \SwitchBox\Iface\Json */
    protected $json_interface;
    /** @var \SwitchBox\Iface\Admin  */
    protected $admin_interface;

    protected $socket_handlers = array();


    public function __construct(array $seeds, KeyPair $keypair, $udp_port = 42424) {
        $this->start_time = time();

        // Setup generic structures
        $this->mesh = new Mesh($udp_port);
        $this->addSocketHandler($this->mesh);

        $this->txqueue = new TxQueue();

        // Create self node based on keypair
        $this->keypair = $keypair;
        $this->self_node = new Node(0, 0, $keypair->getPublicKey(), null);
        $this->mesh->addNode($this->self_node);

        // Add and connect seeds to the mesh
        foreach ($seeds as $seed) {
            $this->mesh->addNode($seed);
            $this->txqueue->enqueue_packet($seed, Open::generate($this, $seed, null));
        }

        // Create our communication interfaces
        $this->admin_interface = new Iface\Admin(42424);
        $this->addSocketHandler($this->admin_interface);

        $this->json_interface = new Iface\Json(42425);
        $this->addSocketHandler($this->json_interface);
    }

    /**
     * @return \SwitchBox\KeyPair
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
     * @throws \RunTimeException
     */
    public function loop() {
        while (! $this->ended) {

            // Process any items that are inside the transmission queue
            if (! $this->txqueue->isEmpty()) {
                print count($this->txqueue)." packet(s) queued.\n";

                while (!$this->txqueue->isEmpty()) {
                    $item = $this->txqueue->dequeue();

                    $bin_packet = $item['packet'];
                    /** @var $bin_packet Packet */
                    $this->getMesh()->send($bin_packet->encode(), $item['ip'], $item['port']);
                }
            }

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

        }
    }

    /**
     * @param iSockHandler $handler
     */
    function addSocketHandler(iSockHandler $handler) {
        $this->socket_handlers[] = $handler;
    }

    /**
     * @return array
     */
    function getSocketHandlers() {
        return $this->socket_handlers;
    }

    /**
     * @return \SwitchBox\TxQueue
     */
    public function getTxQueue()
    {
        return $this->txqueue;
    }

    /**
     *
     */
    public function endApp() {
        $this->ended = true;
    }


    public function doMaintenance() {
//        print ANSI_CYAN . "*** Maintenance Start". ANSI_RESET . "\n";
//        $this->_seekNodes();
//        $this->_connectToNodes();
//        print ANSI_CYAN . "*** Maintenance End". ANSI_RESET . "\n";
    }

    protected function _seekNodes() {
        $hashes = array();
        foreach ($this->getMesh()->getAllNodes() as $node) {
            /** @var $node Node */
            $hashes[] = $node->getName();
        }

        foreach ($hashes as $hash) {
            foreach ($this->getMesh()->getClosestForHash($hash) as $node) {
                /** @var $node Node */

                $stream = new Stream($this, $node);
                $stream->addProcessor("seek", new LineSeek($stream));
                $stream->start(array('hash' => $hash));
            }
        }
    }


    protected function _connectToNodes() {
        // Find all nodes that aren't connected yet
        $nodes = array();
        foreach ($this->getMesh()->getAllNodes() as $node) {
            /** @var $node Node */
            if ($node->isConnected()) continue;
            if ($node->getName() == $this->getSelfNode()->getName()) continue;
            $nodes[] = $node;
        }

        foreach ($nodes as $node) {
            // Send out a ping packet, so they might punch through our NAT (if any)
            $this->getTxQueue()->enqueue_packet($node, Ping::generate($this));

            // Ask (all!??) nodes to let destination connect to use
            foreach ($this->getMesh()->getConnectedNodes() as $seed) {
                /** @var $seed Node */

                // Don't ask ourselves.
                if ($seed->getName() == $this->getSelfNode()->getName()) continue;

                $stream = new Stream($this, $seed);
                $stream->addProcessor("peer", new LinePeer($stream));
                $stream->start(array('hash' => $node->getName()));
            }
        }
    }


    /**
     * @return string
     */
    public function __toString() {
        return "SwitchBox[".$this->getSelfNode()->getName()."]";
    }

}
