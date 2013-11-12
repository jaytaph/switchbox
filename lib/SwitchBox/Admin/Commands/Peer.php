<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet\Line\Peer as LinePeer;

class Peer implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        $nodes = $switchbox->getMesh()->findMatchingNodes($args[0]);
        if (count($nodes) == 0) {
            $buf = "Cannot find any nodes matching '".$args[0]."'. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        foreach ($nodes as $destination) {
            // Send out a ping packet, so they might punch through our NAT (if any)
            $switchbox->getTxQueue()->enqueue_packet($destination, Ping::generate($switchbox));

            // Ask (all!??) nodes to let destination connect to use
            foreach ($switchbox->getMesh()->getConnectedNodes() as $node) {
                /** @var $node Node */

                // Don't ask ourselves.
                if ($node->getName() == $switchbox->getSelfNode()->getName()) continue;

                $stream = new Stream($switchbox, $node);
                $stream->addProcessor("peer", new LinePeer($stream));
                $stream->start(array(
                    'hash' => $destination->getName(),
                ));
            }
        }
    }

    public function help()
    {
        return array(
            "peer [node]",
            "peers to a nodename",
            "Node can be just the start of a node name. It will connect to all matching nodes.",
        );
    }

}
