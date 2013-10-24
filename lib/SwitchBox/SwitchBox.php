<?php

namespace SwitchBox;

use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Hash;
use SwitchBox\DHT\Node;
use SwitchBox\Packet\Open;

// Make sure we are using GMP extension for AES libraries
if (! defined('USE_EXT')) define('USE_EXT', 'GMP');


class SwitchBox {
    /** @var \SwitchBox\KeyPair */
    protected $keypair;
    /** @var DHT\Node */
    protected $self_node;
    /** @var resource */
    protected $sock;
    /** @var DHT\Mesh */
    protected $mesh;

    public function __construct(array $seeds, KeyPair $keypair) {
        $this->mesh = new Mesh($this);

        $this->keypair = $keypair;
        $hash = hash('sha256', Utils::convertPemToDer($this->getKeyPair()->getPublicKey()));
        $this->self_node = new Node(new Hash($hash));

        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        foreach ($seeds as $seed) {
            if (! $seed instanceof Seed) continue;
            /** @var $seed Seed */
            $buf = Open::generate($this, $seed, null)->encode();

            print "Sending OPEN packet to ".$seed->getHost().":".$seed->getPort()."\n";
            socket_sendto($this->sock, $buf, strlen($buf), 0, $seed->getHost(), $seed->getPort());
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
            print "loop() Waiting for data...";
            $ip = "";
            $port = 0;
            socket_recvfrom($this->sock, $buf, 2048, 0, $ip, $port);
            print "\n";

            print "loop() Connection from: $ip : $port\n";

            $packet = Packet::decode($this, $buf);
            $packet->setFrom($ip, $port);
            if ($packet == NULL) {
                print "loop() Unknown data. Not a packet!\n";
                continue;
            }

            print "loop() Incoming '".$packet->getType(true)."' packet from ".$packet->getFromIp().":".$packet->getFromPort()."\n";

            if ($packet->getType() != Packet::TYPE_OPEN) {
                printf ("loop() Cannot decode this type of packet yet :(\n");
                continue;
            }

            // Check packet type, decode correct packet type...
            Open::process($this, Packet::decode($this, $buf));
        }

    }

}
