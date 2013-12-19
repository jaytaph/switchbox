<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\Packet\Line\Channel;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet\Line\Processor\Peer as LinePeer;

class Peer implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $nodes = $switchbox->getMesh()->findMatchingNodes($args[0]);
        if (count($nodes) == 0) {
            $buf = "Cannot find any nodes matching '".$args[0]."'. Try and 'seek' first...\n";
            socket_write($sock, $buf, strlen($buf));
            return;
        }

        foreach ($nodes as $destination) {
            // Send out a ping packet, so they might punch through our NAT (if any)
            $switchbox->send($destination, Ping::generate($switchbox));

            // Ask (all!??) nodes to let destination connect to use
            foreach ($switchbox->getMesh()->getConnectedNodes() as $node) {

                // Don't ask ourselves.
                if ($node->getName() == $switchbox->getSelfNode()->getName()) continue;

                $channel = new Channel($switchbox, $node);
                $channel->addProcessor("peer", new LinePeer($channel));
                $channel->start(array(
                    'hash' => $destination->getName(),
                ));
            }
        }
    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "peer [node]",
            "peers to a nodename",
            "Node can be just the start of a node name. It will connect to all matching nodes.",
        );
    }

}
