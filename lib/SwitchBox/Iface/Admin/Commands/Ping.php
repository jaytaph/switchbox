<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\Packet\Line\Processor\Seek as LineSeek;
use SwitchBox\Packet\Line\Channel;
use SwitchBox\SwitchBox;

class Ping implements iCmd {

    /**
     * @param SwitchBox $switchbox
     * @param SockHandler $handler
     * @param $sock
     * @param $args
     */
    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        if ($args[0] == "all") {
            $nodes = $switchbox->getMesh()->getAllNodes();
        } else {
            $nodes = $switchbox->getMesh()->findMatchingNodes($args[0]);
        }

        foreach ($nodes as $destination) {
            $this->_ping($switchbox, $destination->getName());
        }
    }

    /**
     * @param SwitchBox $switchbox
     * @param $hash
     */
    protected function _ping(SwitchBox $switchbox, $hash) {
        $node = $switchbox->getMesh()->getNode($hash);

        $channel = new Channel($switchbox, $node);
        $channel->addProcessor("seek", new LineSeek($channel));
        $channel->start(array(
            'hash' => $switchbox->getSelfNode()->getName(),
        ));
    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "ping [node|all]",
            "pings a nodename",
            "Node can be just the start of a node name. It will ping all matching nodes.",
        );
    }

}
