<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Seek implements iLineProcessor {

    static function process(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "**** PROCESSING SEEK: \n";
        $header = $packet->getHeader();

        if (! isset($header['see'])) return;
        print_r($header['see']);

        foreach ($header['see'] as $see) {
            list($hash, $ip, $port) = explode(',', $see, 3);

            $node = $switchbox->getMesh()->getNode($hash);
            if ($node) {
                // This node is already present. But we might be able to update IP and PORT
                if ($node->getIp() != $ip) {
                    print "*** Changing IP from ".$node->getIp().":".$node->getPort()." to ".$ip.":".$port."\n";
                    $node->setIp($ip);
                    $node->setPort($port);
                }
            } else {
                $node = new Node($hash);
                $node->setIp($ip);
                $node->setPort($port);
            }

            $stream = new Stream($switchbox, $node, "peer", new Peer());
            $stream->send(Peer::generate($stream, $hash));
        }
    }

    static function generate(Stream $stream, $hash)
    {
        $header = array(
            'c' => $stream->getId(),
            'type' => 'seek',
            'seek' => $hash,
            'seq' => $stream->getNextSequence(),
            'ack' => $stream->getLastAck(),
        );

        return new Packet($stream->getSwitchBox(), $header, null);
    }

}
