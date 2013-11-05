<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Line\Connect;
use SwitchBox\Packet\Line\Peer;
use SwitchBox\Packet\Line\Seek;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

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
        $inner_packet = $cipher->decrypt($packet->getBody());

        $inner_packet = Packet::decode($switchbox, $inner_packet);

        $inner_header = $inner_packet->getHeader();
        $stream = $from->getStream($inner_header['c']);
        if (! $stream) {
            // Stream hasn't been opened yet. Let's create a stream
            switch ($inner_header['type']) {
                case "connect" :
                    $stream = new Stream($switchbox, $from, "connect", new Connect(), $inner_header['c']);
                    break;
//                case "peer" :
//                    $stream = new Stream($switchbox, $from, "peer", new Peer(), $inner_header['c']);
//                    break;
//                case "seek" :
//                    $stream = new Stream($switchbox, $from, "seek", new Seek(), $inner_header['c']);
//                    break;
                default :
                    throw new \RuntimeException("Unknown incoming type in line: ".print_r($inner_header, true));
                    break;
            }
        }
        $stream->process($inner_packet);
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
        $cipher->setKey($to->getEncryptionKey());
        $body = $cipher->encrypt($body);

        return new Packet($switchbox, $header, $body);
    }


}
