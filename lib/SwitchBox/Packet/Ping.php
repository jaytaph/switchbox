<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;

class Ping extends PacketHandler {

    /**
     * Process an empty packet
     *
     * @param Packet $packet
     * @return null|Node
     * @throws \DomainException
     */
    public function process(Packet $packet) {
        // Do nothing
    }

    /**
     */
    static public function generate(SwitchBox $switchbox) {
        return new Packet($switchbox, array(), null);

    }

}
