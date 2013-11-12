<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\SwitchBox;

class distance implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        foreach ($switchbox->getMesh()->getOrderedNodes($switchbox->getSelfNode()->getHash()) as $node) {
            /** @var $node Node */
            $me_hash = $switchbox->getSelfNode()->getHash();
            $they_hash = $node->getHash();
            $buf = sprintf ("%s %s : %d\n", (string)$me_hash, (string)$they_hash, $me_hash->distance($they_hash));
            socket_write($sock, $buf, strlen($buf));
        }
    }

    public function help()
    {
        return array(
            "closest ",
            "display hashes distan",
            "No additional help",
        );
    }

}
