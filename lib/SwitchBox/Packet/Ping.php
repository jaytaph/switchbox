<?php

namespace SwitchBox\Packet;

use phpecc\EcDH;
use phpecc\NISTcurve;
use phpecc\Point;
use phpecc\PrivateKey;
use phpecc\PublicKey;
use phpecc\Utilities\Gmp;
use SwitchBox\DHT\Host;
use SwitchBox\DHT\Node;
use SwitchBox\KeyPair;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class Ping {

    /**
     * Process an empty packet
     *
     * @param SwitchBox $switchbox
     * @param Packet $packet
     * @return null|Node
     * @throws \DomainException
     */
    static function process(SwitchBox $switchbox, Packet $packet) {
    }

    /**
     */
    static function generate(SwitchBox $switchbox) {
        return new Packet($switchbox, array(), null);

    }

}
