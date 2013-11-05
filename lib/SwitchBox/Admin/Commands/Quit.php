<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\SwitchBox;

class Quit implements iCmd {

    function help()
    {
        return array(
            "quit",
            "quits connection",
            "No additional help",
        );
    }

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $buf = "ktnxbai!\n";
        socket_write($sock, $buf, strlen($buf));

        $switchbox->closeSock($sock);
    }

}
