<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Chat as LineChat;

class Xfer implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = array_shift($args);
        $file = array_shift($args);

        $node = $switchbox->getMesh()->getNode($hash);
        if (! $node) {
            $buf = "Cannot find any nodes matching '$hash'. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }
        if (! $node->isConnected()) {
            $buf = "Not yet connected to node '$hash'\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        $stream = new Stream($switchbox, $node);
        $stream->addProcessor("_xfer", new LineChat($stream));
        $header = $stream->createOutStreamHeader('_xfer', array(
            '_' => array(
                'cmd' => 'fetch',
                'name' => $file,
            )
        ), false);

        print ANSI_BLUE;
        print_r($header);
        print ANSI_RESET;
        $stream->send(new Packet($switchbox, $header, null));
    }

    public function getHelp()
    {
        return array(
            "xfer [node] [file]",
            "Transfers a file.",
            "No additional help available.",
        );
    }

}
