<?php

// @TODO: Is this actually needed?

namespace SwitchBox;

use SwitchBox\DHT\Hash;
use SwitchBox\DHT\Node;
use SwitchBox\DHT\Mesh;

class DHT {

    /** @var DHT\Mesh */
    protected $mesh;

    function __construct() {
        $this->mesh = new Mesh();
    }

    function seen(Hash $hash) {
        return $this->mesh->nodeExists($hash) ? $this->mesh->getNode($hash) : null;
    }

    function add(Hash $hash) {
        if ($this->mesh->nodeExists($hash)) return;
        $this->mesh->addNode(new Node($hash));
    }

}
