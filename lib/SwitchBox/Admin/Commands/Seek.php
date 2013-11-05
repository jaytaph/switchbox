<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Packet\Ping;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Seek as LineSeek;

class seek implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = $args[0];
        foreach ($switchbox->getMesh()->getConnectedNodes() as $node) {
            /** @var $node Node */

//            // Don't ask ourselves
//            if ($node->getName() == $switchbox->getSelfNode()->getName()) continue;

//            $switchbox->getTxQueue()->enqueue_packet($node, Ping::generate($switchbox));

            $stream = new Stream($switchbox, $node, "seek", new LineSeek());
            $stream->send(LineSeek::generate($stream, $hash));
        }
//        $buf = "No help today...\n";
//        socket_write($sock, $buf, strlen($buf));
    }

    function help()
    {
        return array(
            "seek [node]",
            "seeks and connects to a nodename",
            "No additional help",
        );
    }

}
