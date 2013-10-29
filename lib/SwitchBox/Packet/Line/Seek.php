<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;

class Seek {

    static function process(SwitchBox $switchbox, Packet $packet) {
        $header = $packet->getHeader();
        if ($header['type'] != "line") {
            throw new \InvalidArgumentException("Not a LINE packet");
        }

        $switchbox->getMesh()->getNode($header['line']);
    }

    static function generate(SwitchBox $switchbox, Node $seek)
    {
        $header = array(
            'seek' => $seek->getHash(),
            'type' => 'seek',
            'stream' => "deadbeefcafef00ddeadbeefcafef00d",
            'seq' => 0,
            'ack' => 0,
        );

        return new Packet($switchbox, $header, null);
    }

}
