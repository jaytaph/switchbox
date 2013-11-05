<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\KeyPair;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Connect implements iLineProcessor {

    // We got a incoming connection request. Let's try and connect to there

    static function process(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "PROCESSING CONNECT REQUEST!!!!\n";
        $header = $packet->getHeader();
        if (! isset($header['ip'])) return;
        print_r($header);

        $pub_key = KeyPair::convertDerToPem($packet->getBody());
        $hash = Node::generateNodeName($pub_key);

        // See if this destination is already someone we know
        $destination = $switchbox->getMesh()->getNode($hash);
        if (! $destination) {
            $destination = new Node($header['ip'], $header['port'], $pub_key);
        }

        // Set destination information
        $destination->setIp($header['ip']);
        $destination->setPort($header['port']);
        $destination->setPublicKey($pub_key);

        if ($destination->isConnected()) {
            print "We are connected to: ".(string)$destination.", no need to connect again\n";
            return;
        }

        $switchbox->getTxQueue()->enqueue_packet($destination, Open::generate($switchbox, $destination, null));
    }

    static function generate(Stream $stream, $ip, $port, $pub_key)
    {
        $header = array(
            'c' => $stream->getId(),
            'type' => 'connect',
            'ip' => $ip,
            'port' => (int)$port,
            'seq' => $stream->getNextSequence(),
            'ack' => $stream->getLastAck(),
            'end' => 'true',
        );

        return new Packet($stream->getSwitchBox(), $header, $pub_key);
    }

}
