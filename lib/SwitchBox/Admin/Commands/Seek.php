<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Seek as LineSeek;

class seek implements iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args)
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


    protected function _seek(SwitchBox $switchbox, $hash) {
        // Find the closest connected nodes for the given hash, and ask if they know about $hash
        foreach ($switchbox->getMesh()->getClosestForHash($hash) as $node) {
            /** @var $node Node */

            $stream = new Stream($switchbox, $node);
            $stream->addProcessor("seek", new LineSeek($stream));
            $stream->start(array(
                'hash' => $hash,
            ));
        }
    }

    public function help()
    {
        return array(
            "seek [node|all]",
            "seeks and connects to a nodename",
            "Node can be just the start of a node name. It will connect to all matching nodes.",
        );
    }

}
