<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\Packet\Line\Channel;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Processor\Seek as LineSeek;

class Seek implements iCmd {

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

        if (count($nodes) == 0) {
            $this->_seek($switchbox, $args[0]);
            return;
        }

        foreach ($nodes as $destination) {
            $this->_seek($switchbox, $destination->getName());
        }
    }

    /**
     * @param SwitchBox $switchbox
     * @param $hash
     */
    protected function _seek(SwitchBox $switchbox, $hash) {
        // Find the closest connected nodes for the given hash, and ask if they know about $hash
        foreach ($switchbox->getMesh()->getClosestForHash($hash) as $node) {
            $channel = new Channel($switchbox, $node);
            $channel->addProcessor("seek", new LineSeek($channel));
            $channel->start(array(
                'hash' => $hash,
            ));
        }
    }


    /**
     * @return array
     */
    public function getHelp()
    {
        return array(
            "seek [node|all]",
            "seeks a nodename",
            "Node can be just the start of a node name. It will connect to all matching nodes.",
        );
    }

}
