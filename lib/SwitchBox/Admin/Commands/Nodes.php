<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\SwitchBox;


class Nodes implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        foreach ($switchbox->getMesh()->getAllNodes() as $node) {
            /** @var $node Node */
            $buf = sprintf("%15s %5d | %-50s | %s\n", $node->getIp(), $node->getPort(), $node->getName(), $node->isConnected() ? 'C' : ' ');
            socket_write($sock, $buf, strlen($buf));
        }
    }

    function help()
    {
        return array(
            "nodes",
            "Display DHT node information",
            "No additional help",
        );
    }

}
