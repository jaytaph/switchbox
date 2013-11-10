<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\DHT\Node;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Packet\Line\Seek as LineSeek;

class seek implements iCmd {

    function execute(SwitchBox $switchbox, $sock, $args)
    {
        if (count($args) == 0) return;

        $hashes = array();

        if ($args[0] == "all") {
            // We will seek all nodes, connected and unconnected
            foreach ($switchbox->getMesh()->getAllNodes() as $node) {
                /** @var $node Node */
                $hashes[] = $node->getName();
            }
        } else {
            $hashes = $args;
        }

        foreach ($hashes as $hash) {
            $this->_seek($switchbox, $hash);
        }
    }


    protected function _seek(SwitchBox $switchbox, $hash) {
        // Find the closest connected nodes for the given hash, and ask if they know about $hash
        foreach ($switchbox->getMesh()->getClosestForHash($hash) as $node) {
            /** @var $node Node */

            $stream = new Stream($switchbox, $node, "seek", new LineSeek());
            $stream->send(LineSeek::outRequest($stream, array(
                'hash' => $hash,
            )));
        }
    }

    function help()
    {
        return array(
            "seek [node|all]",
            "seeks and connects to a nodename",
            "No additional help",
        );
    }

}
