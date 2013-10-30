<?php

namespace SwitchBox\DHT;

// An (initial) seed with all information needed to connect to it.

use SwitchBox\KeyPair;

class Host {
    protected $name;               // Hex string of hash / nodename
    protected $public_key;         // Public key of the node
    protected $ip;                 // IP that connected
    protected $port;               // Port that connected

    public function __construct($host_or_ip, $port, $public_key) {
        $this->ip = gethostbyname($host_or_ip);
        $this->port = $port;
        $this->public_key = $public_key;

        $this->name = hash('sha256', KeyPair::convertPemToDer($this->public_key));
    }

    public function getName() {
        return $this->name;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPublicKey() {
        return $this->public_key;
    }

    function __toString() {
        return $this->getIp().":".$this->getPort()."[".$this->getName()."]";
    }

}
