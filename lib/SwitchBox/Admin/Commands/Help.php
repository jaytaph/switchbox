<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\SwitchBox;

class Help implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        $buf = "No help today\n";
        socket_write($sock, $buf, strlen($buf));
    }

    function help()
    {
        return array(
            "help <command>",
            "Displays help on all commands. Type 'help <command>' for additional help\n",
            "No additional help",
        );
    }

}
