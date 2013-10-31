<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Seek implements iLineProcessor {

    static function process(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "PROCESSING SEEK!!!!\n";
        $header = $packet->getHeader();

        if (! isset($header['see'])) return;

        foreach ($header['see'] as $see) {
            list($hash, $ip, $port) = explode(',', $see, 3);
            var_dump($hash);

            $node = $switchbox->getMesh()->getNode($hash);
            if (!$node) {
                print "Unknown node: ".$hash."\n";
                // We need to open a connection to these nodes... or something??
            } else {
                print "We know about: ".$node->getName()."\n";
            }
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
