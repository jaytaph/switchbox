<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\SwitchBox;


class Whoami implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $node = $switchbox->getSelfNode();
        $buf = sprintf("%15s %5d | %-50s\n", $node->getIp(), $node->getPort(), $node->getName());
        socket_write($sock, $buf, strlen($buf));
    }

    function help()
    {
        return array(
            "whoami",
            "Display node information of yourself",
            "No additional help",
        );
    }

}
