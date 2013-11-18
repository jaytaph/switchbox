<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;


class Whoami implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $node = $switchbox->getSelfNode();
        $buf = sprintf(ANSI_BLUE . "%15s ".ANSI_GREEN . "%5d".ANSI_RESET." | ".ANSI_YELLOW."%-50s".ANSI_RESET."\n",
            $node->getIp(), $node->getPort(), $node->getName()
        );

        socket_write($sock, $buf, strlen($buf));
    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "whoami",
            "Display node information of yourself",
            "No additional help",
        );
    }

}
