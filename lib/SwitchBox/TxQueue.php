<?php

// Pretty much a simple wrapper around splqueue. Can be turned into something wonderful later on

namespace SwitchBox;

use SwitchBox\DHT\Host;

class TxQueue extends \SplQueue {

    // This is why PHP needs method overloading... :(
    function enqueue_packet(Host $host, Packet $packet) {
        parent::enqueue(array('ip' => $host->getIp(), 'port' => $host->getPort(), 'packet' => $packet));
    }

}
