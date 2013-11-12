<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Members as LineMembers;
use SwitchBox\Packet\Line\Chat as LineChat;

class Members implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        list($room, $hash) = explode("@", $args[0]);

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
        $stream->addProcessor("_chat", new LineChat($stream));
        $stream->start(array(
            'room' => (string)$room,
            'nick' => 'jayphp',
        ));


        $stream = new Stream($switchbox, $node);
        $stream->addProcessor("_members", new LineMembers($stream));
        $stream->start(array(
            'room' => (string)$room,
        ));
    }

    public function help()
    {
        return array(
            "members room@node",
            "find members of room",
            "No additional help available",
        );
    }

}
