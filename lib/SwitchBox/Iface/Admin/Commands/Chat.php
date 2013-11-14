<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Chat as LineChat;

class Chat implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = array_shift($args);
        $stream_id = array_shift($args);

        $text = join(" ", $args);

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

        $stream = new Stream($switchbox, $node, $stream_id);
        $stream->addProcessor("chat", new LineChat($stream));
        $header = $stream->createOutStreamHeader('', array(
            '_' => array(
                'message' => $text,
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
            "chat [stream] [room@node] text",
            "peers to a nodename",
            "Node can be just the start of a node name. It will connect to all matching nodes.",
        );
    }

}
