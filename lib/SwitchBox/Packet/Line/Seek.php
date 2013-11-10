<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Seek implements iLineProcessor {

    static function inRequest(SwitchBox $switchbox, Node $node, Packet $packet)
    {
        print "PROCESSING SEEK IN REQUEST!!!!\n";
    }

    static function inResponse(SwitchBox $switchbox, Node $node, Packet $packet)
    {
        print "PROCESSING SEEK REQUEST!!!!\n";
        $header = $packet->getHeader();

        if (! isset($header['see'])) return;
        print_r($header['see']);

        foreach ($header['see'] as $see) {
            list($hash, $ip, $port) = explode(',', $see, 3);

            $node = $switchbox->getMesh()->getNode($hash);
            if ($node) {
                // This node is already present. But we might be able to update IP and PORT
                if ($node->getIp() != $ip) {
                    print "*** Changing existing IP from ".$node->getIp().":".$node->getPort()." to ".$ip.":".$port."\n";
                    $node->setIp($ip);
                    $node->setPort($port);
                }
            } else {
                // Unknown node, just add it to our list
                $switchbox->getMesh()->addNode(new Node($ip, $port, null, $hash));
            }

        }
    }

    static function outResponse(Stream $stream, array $args) {
        $hash = $args['hash'];

        $nodes = array();
        foreach ($stream->getSwitchBox()->getMesh()->getClosestForHash($hash, 5) as $node) {
            /** @var $node Node */
            $nodes[] = $node->getName();
        }

        $header = array(
            'c' => $stream->getId(),
            'type' => 'seek',
            'see' => $nodes,
            'seq' => $stream->getNextSequence(),
            'ack' => $stream->getLastAck(),
        );

        print_r($header);
        return new Packet($stream->getSwitchBox(), $header, null);
    }

    static function outRequest(Stream $stream, array $args)
    {
        $hash = $args['hash'];

        $header = $stream->createOutStreamHeader('seek', array('seek' => $hash));
        return new Packet($stream->getSwitchBox(), $header, null);
    }

}
