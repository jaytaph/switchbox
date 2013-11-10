<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\KeyPair;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Connect implements iLineProcessor {

    // We got a incoming connection request. Let's try and connect to there

    static function inResponse(SwitchBox $switchbox, Node $node, Packet $packet)
    {
        // We've sent out a request onto a stream, and we get a response back
        print "inResponse Connect\n";
    }


    static function inRequest(SwitchBox $switchbox, Node $node, Packet $packet)
    {
        // We've got an incoming request for something

        print "inRequest Connect\n";
        $header = $packet->getHeader();
        if (! isset($header['ip'])) return;
        print_r($header);

        $pub_key = KeyPair::convertDerToPem($packet->getBody());
        $hash = Node::generateNodeName($pub_key);

        // See if this destination is already someone we know
        $destination = $switchbox->getMesh()->getNode($hash);
        if (! $destination) {
            $destination = new Node($header['ip'], $header['port'], $pub_key, null);
        }

        // Set destination information
        $destination->setIp($header['ip']);
        $destination->setPort($header['port']);
        $destination->setPublicKey($pub_key);

        if ($destination->isConnected()) {
            print ANSI_YELLOW . "We are connected to: ".(string)$destination.", no need to connect again, but we still do". ANSI_RESET . "\n";
//            return;
        }

        print_r($destination->getInfo());

        $switchbox->getTxQueue()->enqueue_packet($destination, Open::generate($switchbox, $destination, null));
    }

    static function outResponse(Stream $stream, array $args)
    {
        $ip = $args['ip'];
        $port = $args['port'];
        $pub_key = $args['pub_key'];

        print "outResponse Connect\n";
        $header = $stream->createOutStreamHeader('', array(), true);
        return new Packet($stream->getSwitchBox(), $header, $pub_key);
    }

    static function outRequest(Stream $stream, array $args)
    {
        print "outRequest Connect\n";

        $ip = $args['ip'];
        $port = $args['port'];
        $pub_key = $args['pub_key'];

        // Called whenever we want to request something to the other side
        $header = $stream->createOutStreamHeader('connect', array(
            'ip' => $ip,
            'port' => (int)$port,

        ));
        return new Packet($stream->getSwitchBox(), $header, $pub_key);
    }

}
