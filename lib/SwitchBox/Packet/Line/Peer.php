<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Peer implements iLineProcessor {

    static function inRequest(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "PEER INREQUEST\n";
        $header = $packet->getHeader();
        $body = $packet->getBody();
        print_r($header);
        print_r($body);

        // Got a peer request. Do a middle-man connection
    }

    static function inResponse(SwitchBox $switchbox, Node $node, Packet $packet) {
        print "PEER INRESPONSE\n";
        $header = $packet->getHeader();
        $body = $packet->getBody();
        print_r($header);
        print_r($body);

        // Nothing to do.. the response is just an ack

//        $ip = $switchbox->getSelfNode()->getIp();
//        $port = $switchbox->getSelfNode()->getPort();
//        $pub_key = $switchbox->getKeyPair()->getPublicKey(KeyPair::FORMAT_DER);

//        $host = new Host($ip, $port, $pub_key);
//        $switchbox->getMesh()->addHost($host);
//        // we should send an open packet, just like a normal seed
//        $hash = Host::generateNodeName($pub_key);
//        $node = $switchbox->getMesh()->getNode($hash);
//        if (! $node) {
//            // We don't know about this node. Let's connect to it...
//            $host = new Host($ip, $port, $pub_key);
//            $switchbox->getTxQueue()->enqueue_packet($host, Open::generate($switchbox, $host, null));
//        }

//        $stream = new Stream($switchbox, $node, "connect", new Connect());
//        $stream->send(Connect::generate($stream, $ip, $port, $pub_key));
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
        $hash = $args['hash'];

        $header = $stream->createOutStreamHeader('peer', array('peer' => $hash));
        return new Packet($stream->getSwitchBox(), $header, null);
    }

}
