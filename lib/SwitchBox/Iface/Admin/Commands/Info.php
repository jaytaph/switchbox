<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Processor\Peer as LinePeer;

class Info implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
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
            $buf = "  " . $stream;
            socket_write($sock, $buf, strlen($buf));
        }

        $buf = "\n";
        socket_write($sock, $buf, strlen($buf));

    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "info [node]",
            "Info about node.",
            "No additional help.",
        );
    }

}
