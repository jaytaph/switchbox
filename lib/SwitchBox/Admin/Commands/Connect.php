<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\Packet\Line\Connect as LineConnect;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Connect implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = $args[0];
        $node = $switchbox->getMesh()->getNode($hash);
        if (! $node) {
            $buf = "Cannot find node.\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        $stream = new Stream($switchbox, $node, "connect", new LineConnect());
        $stream->send(LineConnect::generate($stream, $node->getIp(), $node->getPort(), $node->getPublicKey()));
    }

    function help()
    {
        return array(
            "connect [node]",
            "connects (back) to a nodename",
            "No additional help",
        );
    }

}
