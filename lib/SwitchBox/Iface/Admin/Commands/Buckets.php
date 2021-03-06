<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\DHT\Bucket;
use SwitchBox\DHT\Node;
use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;

class Buckets implements iCmd {

    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args)
    {
        $buckets = $switchbox->getMesh()->getBuckets();
        if (count($args) > 0 && isset($buckets[$args[0]])) {
            // Display only this bucket
            $buckets = array($args[0] => $buckets[$args[0]]);
        }

        foreach ($buckets as $k => $v) {
            $this->_displayBucket($k, $v, $sock, $switchbox);
        }
    }

    protected function _displayBucket($idx, Bucket $bucket, $sock, SwitchBox $switchbox) {
        if (count($bucket) == 0) return;

        $buf = sprintf ("Bucket %d [Items: %s  Evictions: %s]\n", $idx, count($bucket), $bucket->getEvictions());
        socket_write($sock, $buf, strlen($buf));

        foreach ($bucket as $node) {
            $this->_displayNode($node, $sock, $switchbox);
        }
    }

    protected function _displayNode(Node $node, $sock, SwitchBox $switchbox) {
        $buf = sprintf(ANSI_BLUE . "%15s ".ANSI_GREEN . "%5d".ANSI_RESET." | ".ANSI_YELLOW."%-50s".ANSI_RESET." | ".ANSI_WHITE."%s%s%s".ANSI_RESET." | ".ANSI_WHITE."%s".ANSI_RESET."\n",
            $node->getIp(), $node->getPort(), $node->getName(),
            $node->getName() == $switchbox->getSelfNode()->getName() ? 'S' : ' ',
            $node->isConnected() ? 'C' : ' ',
            $node->hasPublicKey() ? 'P' : ' ',
            $node->getHealth()
        );
        socket_write($sock, $buf, strlen($buf));
    }

    public function getHelp()
    {
        return array(
            "buckets <index>",
            "Display DHT bucket information",
            "No additional help",
        );
    }

}
