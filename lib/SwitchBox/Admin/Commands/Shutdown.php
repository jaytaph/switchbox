<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\SwitchBox;

class Shutdown implements iCmd {

    function help()
    {
        return array(
            "shutdown",
            "shutdown telehash server",
            "No additional help",
        );
    }

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $buf = "ktnxbai!\n";
        socket_write($sock, $buf, strlen($buf));

        $switchbox->endApp();
    }

}
