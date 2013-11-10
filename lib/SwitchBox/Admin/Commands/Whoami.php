<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\SwitchBox;


class Whoami implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $node = $switchbox->getSelfNode();
        $buf = sprintf(ANSI_BLUE . "%15s ".ANSI_GREEN . "%5d".ANSI_RESET." | ".ANSI_YELLOW."%-50s".ANSI_RESET."\n",
            $node->getIp(), $node->getPort(), $node->getName()
        );

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
