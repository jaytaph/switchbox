<?php

namespace SwitchBox;

use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Hash;
use SwitchBox\DHT\Node;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Open;

// Make sure we are using GMP extension for AES libraries
if (! defined('USE_EXT')) define('USE_EXT', 'GMP');


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

    public function __construct(array $seeds, KeyPair $keypair) {
        // Setup generic structures
        $this->mesh = new Mesh($this);
        $this->txqueue = new TxQueue();

        // Create self node based on keypair
        $this->keypair = $keypair;
        $hash = hash('sha256', Utils::convertPemToDer($this->getKeyPair()->getPublicKey()));
        $this->self_node = new Node(new Hash($hash));

        // Setup UDP mesh socket
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->sock);
        foreach ($seeds as $seed) {
            if (! $seed instanceof Seed) continue;
            $this->txqueue->enqueue_packet($seed->getHost(), $seed->getPort(), Open::generate($this, $seed, null));
        }

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



    public function __toString() {
        return "SwitchBox[".$this->getSelfNode()->getHash()."]";
    }

    public function loop() {
        while (true) {
            print "loop() Checking TX queue...";
            if (! $this->txqueue->isEmpty()) {
                print count($this->txqueue)." packets queued.\n";

                while (!$this->txqueue->isEmpty()) {
                    $item = $this->txqueue->dequeue();

                    print "Sending packet to ".$item['ip'].":".$item['port']."\n";
                    $bin_packet = $item['packet'];
                    $bin_packet = $bin_packet->encode();
                    socket_sendto($this->sock, $bin_packet, strlen($bin_packet), 0, $item['ip'], $item['port']);
                }

            } else {
                print "empty\n";
            }

            print "loop() select(): ";
            $r = array($this->sock);
            $w = $x = NULL;
            $ret = socket_select($r, $w, $x, self::SELECT_TIMEOUT);
            if ($ret === false) {
                die("socket_select() failed: ".socket_strerror(socket_last_error()."\n"));
            }
            if ($ret == 0) {
                print "Timeout\n";
                continue;
            }
            print "\n";
            foreach ($r as $sock) {
                print "Retrieving info from socket...\n";

                $ip = ""; $port = 0;
                socket_recvfrom($sock, $buf, 2048, 0, $ip, $port);
                print "loop() Connection from: $ip : $port\n";

                $packet = Packet::decode($this, $buf, $ip, $port);
                if ($packet == NULL) {
                    print "loop() Unknown data. Not a packet!\n";
                    continue;
                }

                print "loop() Incoming '".$packet->getType(true)."' packet from ".$packet->getFromIp().":".$packet->getFromPort()."\n";

                // @TODO: Packet should already have it's processor:  $packet->process($this, $buf);

                if ($packet->getType() == Packet::TYPE_OPEN) {
                    // Check packet type, decode correct packet type...
                    /** @var $node DHT\Node */
                    $node = Open::process($this, $packet);

                    // Try and do a seek to ourselves
                    $packet = Line::generate($this, $node, Line\Seek::generate($this, $node));
                    $this->txqueue->enqueue_packet($node->getIp(), $node->getPort(), $packet);

                    continue;
                }
                if ($packet->getType() == Packet::TYPE_LINE) {
                    Line::process($this, $packet);

                    continue;
                }

                printf ("loop() Cannot decode this type of packet yet :(\n");
                continue;
            }
        }

    }

}
