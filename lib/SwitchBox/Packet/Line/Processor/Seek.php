<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;

class Seek extends StreamProcessor {

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_YELLOW;
        print_r($header);
        print ANSI_RESET;

        if (isset($header['see'])) {
            foreach ($header['see'] as $see_line) {
                $this->_see($see_line);
            }
        }

        if (isset($header['seek'])) {
            $this->_seek($header['seek']);
        }
    }

    protected function _seek($line) {
        $nodes = array();
        foreach ($this->getSwitchBox()->getMesh()->getClosestForHash($line, 5) as $node) {
            $nodes[] = $node->getName() . "," . $node->getIp() . "," . $node->getPort();
        }

        // Send out our see-lines to the requestor
        $header = $this->getStream()->createOutStreamHeader('seek', array('see' => $nodes), true);
        $this->getStream()->send(new Packet($header, null));
    }


    protected function _see($see_line) {
        list($hash, $ip, $port) = explode(',', $see_line, 3);

        $node = $this->getSwitchBox()->getMesh()->getNode($hash);
        if ($node) {
            // This node is already present. But we might be able to update IP and PORT
            if ($node->getIp() != $ip) {
                print "*** Changing existing IP from ".$node->getIp().":".$node->getPort()." to ".$ip.":".$port."\n";
                $node->setIp($ip);
                $node->setPort($port);
            }
        } else {
            // Unknown node, just add it to our list
            $this->getSwitchBox()->getMesh()->addNode(new Node($ip, $port, null, $hash));
        }
    }


    public function generate(array $args)
    {
        print "*** generate SEEK\n";
        $hash = $args['hash'];

        $header = $this->getStream()->createOutStreamHeader('seek', array('seek' => $hash), false);
        return new Packet($header, null);
    }

}
