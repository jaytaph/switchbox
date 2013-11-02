<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Host;
use SwitchBox\DHT\Node;
use SwitchBox\KeyPair;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Connect implements iLineProcessor {

    static function process(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "PROCESSING CONNECT!!!!\n";
        $header = $packet->getHeader();

        $pub_key = KeyPair::convertDerToPem($packet->getBody());
        print_r($header);
        print_r($pub_key);

        // we should send an open packet, just like a normal seed
        $hash = Host::generateNodeName($pub_key);
        $node = $switchbox->getMesh()->getNode($hash);
        if (! $node) {
            // We don't know about this node. Let's connect to it...
            print "Unknown node: ".$hash."\n";
            $host = new Host($ip, $port, $pub_key);
            $switchbox->getTxQueue()->enqueue_packet($host, Open::generate($switchbox, $host, null));
        } else {
            print "We know about: ".$node->getName()."\n";
        }
    }

    static function generate(Stream $stream, $ip, $port, $pub_key)
    {
        $header = array(
            'c' => $stream->getId(),
            'type' => 'connect',
            'ip' => $ip,
            'port' => $port,
            'seq' => $stream->getNextSequence(),
            'ack' => $stream->getLastAck(),
        );

        return new Packet($stream->getSwitchBox(), $header, $pub_key);
    }

}
