<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;


class Last implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        foreach ($handler->getSockInfo() as $log) {
            $dt = new \DateTime("@".$log['date_in']);
            $buf = sprintf(ANSI_YELLOW. "%15s ".ANSI_GREEN . "%s".ANSI_RESET."\n",
                $dt->format("D d-M-y H:i:s"), $log['ip']
            );
            socket_write($sock, $buf, strlen($buf));
        }

    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "last",
            "Display incoming admin connections",
            "No additional help",
        );
    }

}
