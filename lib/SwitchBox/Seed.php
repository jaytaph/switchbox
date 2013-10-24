<?php

namespace SwitchBox;

// An (initial) seed with all information needed to connect to it.

class Seed {
    protected $host;
    protected $port;
    protected $hash;
    protected $public_key;

    public function __construct($host, $port, $hash, $public_key) {
        $this->host = gethostbyname($host);
        $this->port = $port;
        $this->hash = $hash;
        $this->public_key = $public_key;

        hash('sha256', Utils::convertPemToDer($this->public_key));
    }

    public function getHash() {
        return $this->hash;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPublicKey() {
        return $this->public_key;
    }

    public function __toString() {
        return "SwitchBox[".$this->getHost().":".$this->getPort()."]";
    }
}
