<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;


class Nodes implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $i = 0;
        foreach ($switchbox->getMesh()->getAllNodes() as $node) {
            $buf = sprintf(ANSI_WHITE."#%-3d ".ANSI_BLUE . "%15s ".ANSI_GREEN . "%5d".ANSI_RESET." | ".ANSI_YELLOW."%-50s".ANSI_RESET." | ".ANSI_WHITE."%s%s%s".ANSI_RESET." | ".ANSI_WHITE."%s".ANSI_RESET." | Idle: ".ANSI_WHITE."%s".ANSI_RESET."\n",
                $i++,
                $node->getIp(), $node->getPort(), $node->getName(),
                $node->getName() == $switchbox->getSelfNode()->getName() ? 'S' : ' ',
                $node->isConnected() ? 'C' : ' ',
                $node->hasPublicKey() ? 'P' : ' ',
                $node->getName() == $switchbox->getSelfNode()->getName() ? "self" : $node->getHealth(),
                $node->getName() == $switchbox->getSelfNode()->getName() ? "---"  : (time() - $node->getLastActivityTs())
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
            "nodes",
            "Display DHT node information",
            "No additional help",
        );
    }

}
