<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\KeyPair;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Connect extends streamProcessor {


    function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_CYAN;
        print_r($header);
        print ANSI_RESET;

        // Confirmation packet, ignore this
        if (! isset($header['ip'])) return;

        $pub_key = KeyPair::convertDerToPem($packet->getBody());
        $hash = Node::generateNodeName($pub_key);

        // See if this destination is already someone we know
        $destination = $this->getSwitchBox()->getMesh()->getNode($hash);
        if (! $destination) {
            $destination = new Node($header['ip'], $header['port'], $pub_key, null);
        }

        // Set destination information
        $destination->setIp($header['ip']);
        $destination->setPort($header['port']);
        $destination->setPublicKey($pub_key);

        if ($destination->isConnected()) {
            print ANSI_YELLOW . "We are connected to: ".(string)$destination.", no need to connect again, but we still do". ANSI_RESET . "\n";
        }

        print_r($destination->getInfo());
        $this->getSwitchBox()->getTxQueue()->enqueue_packet($destination, Open::generate($this->getSwitchBox(), $destination, null));
    }


//
//    // We got a incoming connection request. Let's try and connect to there
//
//    static function inResponse(SwitchBox $switchbox, Node $node, Packet $packet)
//    {
//        // We've sent out a request onto a stream, and we get a response back
//        print "inResponse Connect\n";
//    }
//
//
//    static function inRequest(SwitchBox $switchbox, Node $node, Packet $packet)
//    {
//        // We've got an incoming request for something
//
//        print "inRequest Connect\n";
//        $header = $packet->getHeader();
//        if (! isset($header['ip'])) return;
//        print_r($header);
//
//        $pub_key = KeyPair::convertDerToPem($packet->getBody());
//        $hash = Node::generateNodeName($pub_key);
//
//        // See if this destination is already someone we know
//        $destination = $switchbox->getMesh()->getNode($hash);
//        if (! $destination) {
//            $destination = new Node($header['ip'], $header['port'], $pub_key, null);
//        }
//
//        // Set destination information
//        $destination->setIp($header['ip']);
//        $destination->setPort($header['port']);
//        $destination->setPublicKey($pub_key);
//
//        if ($destination->isConnected()) {
//            print ANSI_YELLOW . "We are connected to: ".(string)$destination.", no need to connect again, but we still do". ANSI_RESET . "\n";
////            return;
//        }
//
//        print_r($destination->getInfo());
//
//        $switchbox->getTxQueue()->enqueue_packet($destination, Open::generate($switchbox, $destination, null));
//    }

    function generate(array $args)
    {
        print "*** generate CONNECT\n";
        $header = $this->getStream()->createOutStreamHeader('connect', array('ip' => $args['ip'], 'port' => $args['port']), true);
        print_r($header);
        print_r($args);
        return new Packet($this->getSwitchBox(), $header, KeyPair::convertPemToDer($args['pub_key']));
    }

}
