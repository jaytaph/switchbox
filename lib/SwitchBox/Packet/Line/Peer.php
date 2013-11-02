<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Host;
use SwitchBox\DHT\Node;
use SwitchBox\KeyPair;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Peer implements iLineProcessor {

    static function process(SwitchBox $switchbox, Node $node, Packet $packet) {
        $header = $packet->getHeader();
        $body = $packet->getBody();

        $ip = $switchbox->getSelfNode()->getIp();
        $port = $switchbox->getSelfNode()->getPort();
        $pub_key = $switchbox->getKeyPair()->getPublicKey(KeyPair::FORMAT_DER);

//        // we should send an open packet, just like a normal seed
//        $hash = Host::generateNodeName($pub_key);
//        $node = $switchbox->getMesh()->getNode($hash);
//        if (! $node) {
//            // We don't know about this node. Let's connect to it...
//            $host = new Host($ip, $port, $pub_key);
//            $switchbox->getTxQueue()->enqueue_packet($host, Open::generate($switchbox, $host, null));
//        }

        $stream = new Stream($switchbox, $node, "connect", new Connect());
        $stream->send(Connect::generate($stream, $ip, $port, $pub_key));
    }

    static function generate(Stream $stream, $hash)
    {
        $header = array(
            'c' => $stream->getId(),
            'type' => 'peer',
            'peer' => $hash,
            'seq' => $stream->getNextSequence(),
            'ack' => $stream->getLastAck(),
        );

        return new Packet($stream->getSwitchBox(), $header, null);
    }

}
