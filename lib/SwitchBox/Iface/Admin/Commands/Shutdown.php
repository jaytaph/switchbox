<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\SwitchBox;

class Shutdown implements iCmd {

    public function getHelp()
    {
        return array(
            "shutdown",
            "shutdown telehash server",
            "No additional help",
        );
    }

    public function execute(SwitchBox $switchbox, $sock, $args)
    {
        $buf = "ktnxbai!\n";
        socket_write($sock, $buf, strlen($buf));

        $switchbox->endApp();
    }

}