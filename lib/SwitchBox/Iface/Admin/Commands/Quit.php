<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;

class Quit implements iCmd {

    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "quit",
            "quits connection",
            "No additional help",
        );
    }


    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $buf = "ktnxbai!\n";
        socket_write($sock, $buf, strlen($buf));

        $handler->closeSock($sock);
    }

}
