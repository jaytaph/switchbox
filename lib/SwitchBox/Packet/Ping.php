<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;

class Ping {

    /**
     * Process an empty packet
     *
     * @param SwitchBox $switchbox
     * @param Packet $packet
     * @return null|Node
     * @throws \DomainException
     */
    static public function process(SwitchBox $switchbox, Packet $packet) {
    }

    /**
     */
    static public function generate(SwitchBox $switchbox) {
        return new Packet($switchbox, array(), null);

    }

}
