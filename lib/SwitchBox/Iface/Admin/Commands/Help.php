<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;

class Help implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $it = new \GlobIterator(__DIR__ . "/*.php");
        foreach ($it as $file) {
            $class = "\\SwitchBox\\Iface\\Admin\\Commands\\".ucfirst(strtolower(basename($file, ".php")));
            if (class_exists($class)) {
                $cmd = new $class();
                $tmp = $cmd->getHelp();
                $buf = sprintf("%-30s : %s\n", $tmp[0], $tmp[1]);
                socket_write($sock, $buf, strlen($buf));
            }
        }
    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "help <command>",
            "Displays help on all commands.",
            "Type 'help <command>' for additional help",
        );
    }

}
