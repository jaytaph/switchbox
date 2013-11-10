<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;
use SwitchBox\KeyPair;

class Line {

    /**
     * Process a line packet by decoding and passing it to the correct stream-handler
     *
     * @param SwitchBox $switchbox
     * @param Packet $packet
     * @throws \InvalidArgumentException
     * @throws \DomainException
     */
    static function process(SwitchBox $switchbox, Packet $packet)
    {
        $header = $packet->getHeader();

        // Are we actually a line packet?
        if ($header['type'] != "line") {
            throw new \InvalidArgumentException("Not a LINE packet");
        }

        // Find our node
        $from = $switchbox->getMesh()->findByLine($header['line']);
        if (! $from) {
            print "Cannot find matching node for line ".$header['line'];
            return;
        }

        // Decrypt line body with inner packet
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($from->getDecryptionKey());
//        print "Decrypting with IV/KEY: ".$header['iv']." / ".bin2hex($from->getDecryptionKey())."\n";
        $inner_packet = $cipher->decrypt($packet->getBody());
        $inner_packet = Packet::decode($switchbox, $inner_packet);

        $inner_header = $inner_packet->getHeader();
        $stream = $from->getStream($inner_header['c']);
        if ($stream) {
            print "Processing from stream\n";
            // Process already existing stream
            $stream->process($inner_packet);
            return;
        }

        print "No stream found. Creating something or something...\n";
        print_r($inner_header);

        // Stream hasn't been opened yet. Let's create a stream
        switch ($inner_header['type']) {
            case "connect" :
                print ANSI_YELLOW . "CREATING CONNECT STREAM " . ANSI_RESET . "\n";
                // We don't open a connect stream (not important). But we need to connect to another site
                $node = new Node($inner_header['ip'], $inner_header['port'], KeyPair::convertDerToPem($inner_packet->getBody()));
                $switchbox->getTxQueue()->enqueue_packet($node, Open::generate($switchbox, $node, null));
                break;
//          case "peer" :
//              print ANSI_YELLOW . "CREATING PEER STREAM " . ANSI_RESET . "\n";
//              $stream = new Stream($switchbox, $from, "peer", new Peer(), $inner_header['c']);
//              break;
            case "seek" :
                print ANSI_YELLOW . "CREATING SEEK STREAM " . ANSI_RESET . "\n";
//                $stream = new Stream($switchbox, $from, "seek", new Seek(), $inner_header['c']);
                break;
            default :
                print ANSI_RED . "Unknown incoming type in line: ".print_r($inner_header, true).ANSI_RESET . "\n";
                break;
        }

    }

    /**
     * Generate a complete line packet based on te inner packet
     *
     * @param SwitchBox $switchbox
     * @param Node $to
     * @param Packet $inner_packet
     * @return Packet
     */
    static function generate(SwitchBox $switchbox, Node $to, Packet $inner_packet)
    {
        $header = array(
            'type' => 'line',
            'line' => $to->getLineIn(),
            'iv' => Utils::bin2hex(openssl_random_pseudo_bytes(16), 32),
        );

        $body = $inner_packet->encode();

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
//        print "Encrypting with IV/KEY: ".$header['iv']."/".bin2hex($to->getEncryptionKey())."\n";
        $cipher->setKey($to->getEncryptionKey());
        $body = $cipher->encrypt($body);

        return new Packet($switchbox, $header, $body);
    }


}
