<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
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
            throw new \DomainException("Cannot find matching node for line ".$header['line']);
        }

        // Decrypt line body with inner packet
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($from->getDecryptionKey());
        $inner_packet = $cipher->decrypt($packet->getBody());

        $inner_packet = Packet::decode($switchbox, $inner_packet);
        print_r($inner_packet->getHeader());
        print_r($inner_packet->getBody());

        $inner_header = $inner_packet->getHeader();
        $stream = $from->getStream($inner_header['c']);
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
            'iv' => Utils::bin2hex(openssl_random_pseudo_bytes(16)),
        );

        $body = $inner_packet->encode();

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($to->getEncryptionKey());
        $body = $cipher->encrypt($body);

        return new Packet($switchbox, $header, $body);
    }


}
