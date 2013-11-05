<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Packet\Ping;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Peer as LinePeer;

class Peer implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $hash = $args[0];

        // Find our destination.
        $destination = $switchbox->getMesh()->getNode($hash);
        if (! $destination) {
            $buf = "Cannot find $hash. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        // Send out a ping packet, so they might punch through our NAT (if any)
        $switchbox->getTxQueue()->enqueue_packet($destination, Ping::generate($switchbox));
        $switchbox->getTxQueue()->enqueue_packet($destination, Ping::generate($switchbox));
        $switchbox->getTxQueue()->enqueue_packet($destination, Ping::generate($switchbox));


        // Ask (all!??) nodes to let destination connect to use
        foreach ($switchbox->getMesh()->getConnectedNodes() as $node) {
            /** @var $node Node */

            // Don't ask ourselves.
            if ($node->getName() == $switchbox->getSelfNode()->getName()) continue;

            $stream = new Stream($switchbox, $node, "peer", new LinePeer());
            $stream->send(LinePeer::generate($stream, $hash));
        }
    }

    function help()
    {
        return array(
            "peer [node]",
            "peers to a nodename",
            "No additional help",
        );
    }

}
