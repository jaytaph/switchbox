<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet\Line\Peer as LinePeer;

class Info implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = $args[0];
        $node = $switchbox->getMesh()->getNode($hash);
        if (! $node) {
            $buf = "Cannot find node '$hash'. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        print_r($node->getInfo());
        foreach ($node->getStreams() as $stream) {
            print $stream . "\n";
        }
    }

    function help()
    {
        return array(
            "info [node]",
            "Info about node",
            "No additional help.",
        );
    }

}
