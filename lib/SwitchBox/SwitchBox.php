<?php

namespace SwitchBox;

use SwitchBox\DHT\Host;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Hash;
use SwitchBox\DHT\Node;
use SwitchBox\DHT\Seed;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\Ping;

// Make sure we are using GMP extension for AES libraries
if (! defined('USE_EXT')) define('USE_EXT', 'GMP');


define('ANSI_RESET', "\x1b[0m");
define('ANSI_RED', "\x1b[31;1m");
define('ANSI_GREEN', "\x1b[32;1m");
define('ANSI_YELLOW', "\x1b[33;1m");
define('ANSI_BLUE', "\x1b[34;1m");
define('ANSI_MAGENTA', "\x1b[35;1m");
define('ANSI_CYAN', "\x1b[36;1m");
define('ANSI_WHITE', "\x1b[37;1m");


class SwitchBox {
    const SELECT_TIMEOUT        = 2;        // Nr of seconds before socket_select() will timeout to do housekeeping

    /** @var \SwitchBox\KeyPair */
    protected $keypair;
    /** @var DHT\Node */
    protected $self_node;
    /** @var resource UDP socket connecting to mesh */
    protected $sock;
    /** @var DHT\Mesh */
    protected $mesh;
    /** @var TxQueue */
    protected $txqueue;
    /** @var resource TCP socket for commands */
    protected $cmd_sock;
    /** @var array TCP client sockets */
    protected $cmd_sock_clients = array();
    /** @var bool */
    protected $ended = false;


    public function closeSock($sock) {
        $i = array_search($sock, $this->cmd_sock_clients);
        if ($i !== false) {
            unset($this->cmd_sock_clients[$i]);
            socket_close($sock);
        }
    }

    public function __construct(array $seeds, KeyPair $keypair, $udp_port = 42424) {
        // Setup generic structures
        $this->mesh = new Mesh($this);
        $this->txqueue = new TxQueue();

        // Create self node based on keypair
        $this->keypair = $keypair;
        $hash = Node::generateNodeName($keypair->getPublicKey());
        $this->self_node = new Node($hash);
        $this->self_node->setIp(0);     // We don't know our external IP/Port yet
        $this->self_node->setPort(0);
        $this->mesh->addNode($this->self_node);

        // Setup UDP mesh socket
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->sock);
        socket_bind($this->sock, 0, $udp_port);
        foreach ($seeds as $seed) {
            if (! $seed instanceof Seed) continue;
            $this->getMesh()->addHost($seed);
//            $this->txqueue->enqueue_packet($seed, Ping::generate($this));
            $this->txqueue->enqueue_packet($seed, Open::generate($this, $seed, null));
        }

        // Setup TCP command socket
        $this->cmd_sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->cmd_sock, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->cmd_sock, 0, 42424);
        socket_listen($this->cmd_sock, 1024);
        $this->cmd_sock_clients = array();
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
     * @return KeyPair
     */
    public function getKeyPair()
    {
        return $this->keypair;
    }


    public function tx(Node $to, Packet $packet) {
        $this->txqueue->enqueue_packet($to, $packet->encode());
    }


    public function __toString() {
        return "SwitchBox[".$this->getSelfNode()->getName()."]";
    }

    public function loop() {
        while (! $this->ended) {
            if (! $this->txqueue->isEmpty()) {
                print count($this->txqueue)." packet(s) queued.\n";

                while (!$this->txqueue->isEmpty()) {
                    $item = $this->txqueue->dequeue();

                    print "Sending packet to ".$item['ip'].":".$item['port']."\n";
                    $bin_packet = $item['packet'];
                    $bin_packet = $bin_packet->encode();
                    socket_sendto($this->sock, $bin_packet, strlen($bin_packet), 0, $item['ip'], $item['port']);
                }
            }

            $r = array($this->sock, $this->cmd_sock);
            $r = array_merge($r, $this->cmd_sock_clients);
            $w = $x = NULL;
            $ret = socket_select($r, $w, $x, self::SELECT_TIMEOUT);
            if ($ret === false) {
                die("socket_select() failed: ".socket_strerror(socket_last_error()."\n"));
            }
            if ($ret == 0) {
                // Timeout occurred
                continue;
            }
            print "\n";
            foreach ($r as $sock) {
                if ($sock == $this->sock) {
                    // do UDP telehash packets
                    $this->_loop_udp_packets($sock);
                }
                  if ($sock == $this->cmd_sock) {
                    // do initial TCP connections
                    $this->_loop_tcp_packets($sock);
                }

                if (in_array($sock, $this->cmd_sock_clients)) {
                    // do TCP clients
                    $this->_loop_tcp_packets_client($sock);
                }
            }
        }
    }

    function _loop_tcp_packets_client($sock) {
        print "Receiving info from TCP client socket...\n";
        $s = socket_read($sock, 2048);
        $s = trim($s);

        $args = explode(" ", $s);
        $cmd = ucfirst(strtolower(array_shift($args)));

        // Check if class exists
        $class = "\\SwitchBox\\Admin\\Commands\\".$cmd;
        if (class_exists($class)) {
            $cmd = new $class();
            $cmd->execute($this, $sock, $args);
        } else {
            $buf = "Unknown command ".$cmd.". Type 'help' for all available commands.\n";
            socket_write($sock, $buf, strlen($buf));
        }

        // Display prompt only when we are still having an open TCP socket
        if (in_array($sock, $this->cmd_sock_clients)) {
            $buf = "> ";
            socket_write($sock, $buf, strlen($buf));
        }
    }

    function _loop_tcp_packets($sock) {
        print "Receiving info from TCP socket...\n";
        $sock = socket_accept($sock);
        $this->cmd_sock_clients[] = $sock;

        $buf = "\nWelcome to the TeleHash Admin Panel. \n" .
               "To quit, type 'quit', To seek help, type 'help'\n";
        socket_write($sock, $buf, strlen($buf));

        $buf = "> ";
        socket_write($sock, $buf, strlen($buf));
    }

    function _loop_udp_packets($sock) {
        print "Receiving info from UDP socket...\n";

        $ip = ""; $port = 0;
        socket_recvfrom($sock, $buf, 2048, 0, $ip, $port);
        print "loop() Connection from: $ip : $port\n";

        if ($ip == $this->getSelfNode()->getIp() && $port == $this->getSelfNode()->getPort()) {
            print "Loop() received data from self. Skipping!\n";
            return;
        }

        $packet = Packet::decode($this, $buf, $ip, $port);
        if ($packet == NULL) {
            print "loop() Unknown data. Not a packet!\n";
            return;
        }

        print "loop() Incoming '".ANSI_WHITE . $packet->getType(true). ANSI_RESET."' packet from ".$packet->getFromIp().":".$packet->getFromPort()."\n";

        // @TODO: Packet should already have its processor:  $packet->process($this, $buf);

        if ($packet->getType() == Packet::TYPE_PING) {
            // Do nothing..
            return;
        }

        if ($packet->getType() == Packet::TYPE_OPEN) {
            $node = Open::process($this, $packet);
            if ($node) {
                $node->setIp($packet->getFromIp());
                $node->setPort($packet->getFromPort());
            }

            if (! $node->isConnected()) {
                print ANSI_YELLOW."Node ".(string)$node." is not yet connected. ".ANSI_RESET."\n";
                $this->txqueue->enqueue_packet($node, Open::generate($this, $node, null));
            } else {
                print ANSI_GREEN."Finalized connection with ".(string)$node."!!!!!".ANSI_RESET."\n";
            }

//            // A new connection, let's try and seek ourselves back
//            $this->getTxQueue()->enqueue_packet($node, Ping::generate($this));

            // Try and do a seek to ourselves, this allows us to find our outside IP/PORT
            $stream = new Stream($this, $node, "seek", new Line\Seek());
            $stream->send(Line\Seek::generate($stream, $this->getSelfNode()->getName()));

            return;
        }
        if ($packet->getType() == Packet::TYPE_LINE) {
            Line::process($this, $packet);
            return;
        }

        printf ("loop() Cannot decode this type of packet yet :(\n");
    }

    /**
     * @return \SwitchBox\TxQueue
     */
    public function getTxQueue()
    {
        return $this->txqueue;
    }

    public function endApp() {
        $this->ended = true;
    }



    // Metrics:
    //      Number of packets in
    //      Number of packets out
    //      Number of bytes in
    //      Number of bytes out
    //      Number of misses
    //      Number of known nodes


}
