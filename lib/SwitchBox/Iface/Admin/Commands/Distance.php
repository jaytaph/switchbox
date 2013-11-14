<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\SwitchBox;

class distance implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        foreach ($switchbox->getMesh()->getOrderedNodes($switchbox->getSelfNode()->getHash()) as $node) {
            $me_hash = $switchbox->getSelfNode()->getHash();
            $they_hash = $node->getHash();
            $buf = sprintf ("%s %s : %d\n", (string)$me_hash, (string)$they_hash, $me_hash->distance($they_hash));
            socket_write($sock, $buf, strlen($buf));
        }
    }

    public function getHelp()
    {
        return array(
            "closest <node>",
            "display hashes distance between node and ourselve",
            "No additional help",
        );
    }

}
