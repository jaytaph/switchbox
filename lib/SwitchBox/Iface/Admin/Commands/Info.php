<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet\Line\Peer as LinePeer;

class Info implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = $args[0];
        $node = $switchbox->getMesh()->getNode($hash);
        if (! $node) {
            $buf = "Cannot find node '$hash'. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        $buf = print_r($node->getInfo(), true);
        socket_write($sock, $buf, strlen($buf));

        if ($node->hasPublicKey()) {
            $buf = "\n";
            socket_write($sock, $buf, strlen($buf));
            $buf = "Public Key:\n";
            socket_write($sock, $buf, strlen($buf));

            $buf = $node->getPublicKey();
            socket_write($sock, $buf, strlen($buf));
        }

        $buf = "\n";
        socket_write($sock, $buf, strlen($buf));
        $buf = "Avaliable streams:\n";
        socket_write($sock, $buf, strlen($buf));
        foreach ($node->getStreams() as $stream) {
            $buf = $stream;
            socket_write($sock, $buf, strlen($buf));
        }

        $buf = "\n";
        socket_write($sock, $buf, strlen($buf));

    }

    public function getHelp()
    {
        return array(
            "info [node]",
            "Info about node.",
            "No additional help.",
        );
    }

}
