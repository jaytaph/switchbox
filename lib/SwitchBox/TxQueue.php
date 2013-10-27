<?php

// Pretty much a simple wrapper around splqueue. Can be turned into something wonderful later on

namespace SwitchBox;

class TxQueue extends \SplQueue {

    // This is why PHP needs method overloading... :(
    function enqueue_packet($ip, $port, Packet $packet) {
        parent::enqueue(array('ip' => $ip, 'port' => $port, 'packet' => $packet));
    }

}
