<?php

// Pretty much a simple wrapper around splqueue. Can be turned into something wonderful later on

namespace SwitchBox;

use SwitchBox\DHT\Node;

class TxQueue extends \SplQueue {

    // This is why PHP needs method overloading... :(
    function enqueue_packet(Node $node, Packet $packet) {
        if ($node->getIp() == 0) {
            print ANSI_RED . "Not sending to unknown IP ". ANSI_RESET . "\n";
            return;
        }
        parent::enqueue(array(
            'ip' => $node->getIp(),
            'port' => $node->getPort(),
            'packet' => $packet)
        );
    }

}
