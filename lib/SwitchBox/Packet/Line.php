<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Line\Processor\Connect;
use SwitchBox\Packet\Line\Processor\Peer;
use SwitchBox\Packet\Line\Processor\Seek;
use SwitchBox\Packet\Line\Processor\StreamProcessor;
use SwitchBox\Packet\Line\Stream;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class Line extends PacketHandler {

    /** @var StreamProcessor[] */
    protected $stream_processors = array();         // All the stream processors that are available


    /**
     * Add a custom stream processor
     *
     * @param $type
     * @param $class
     * @throws \InvalidArgumentException
     */
    public function addCustomStreamProcessor($type, $class) {
        $tmp = new $class(null);
        if (! $tmp instanceof StreamProcessor) {
            throw new \InvalidArgumentException("Class must be an instance of StreamProcessor!");
        }

        $this->stream_processors[$type] = $class;
    }


    /**
     * @param $type
     * @return null|StreamProcessor
     */
    public function getCustomStreamProcessor($type) {
        if (isset($this->stream_processors[$type])) {
            return $this->stream_processors[$type];
        }
        return null;
    }


    /**
     * Process a line packet by decoding and passing it to the correct stream-handler
     *
     * @param Packet $packet
     * @return mixed|void
     * @throws \InvalidArgumentException
     */
    public function process(Packet $packet)
    {
        $header = $packet->getHeader();
        print_r($header);

        // Are we actually a line packet?
        if ($header['type'] != "line") {
            throw new \InvalidArgumentException("Not a LINE packet");
        }

        // Find our node
        $from = $this->getSwitchBox()->getMesh()->findByLine($header['line']);
        if (! $from) {
            print "Cannot find matching node for line ".$header['line']."\n";
            return;
        }

        // Decrypt line body with inner packet
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($from->getDecryptionKey());
//        print "Decrypting with IV/KEY: ".$header['iv']." / ".bin2hex($from->getDecryptionKey())."\n";
        $inner_packet = Packet::decode($this->getSwitchBox(), $cipher->decrypt($packet->getBody()));

        $inner_header = $inner_packet->getHeader();
        print_r($inner_header);
        $stream = $from->getStream($inner_header['c']);
        if (! $stream) {
            $stream = new Stream($this->getSwitchBox(), $from, $inner_header['c']);

            // There is an incoming request. We must respond to it
            print "No stream found. Creating new stream...\n";

            // Stream hasn't been opened yet. Let's create a stream
            switch ($inner_header['type']) {
                case "connect" :
                    $stream->addProcessor("connect", new Connect($stream));
                    break;
                case "peer" :
                    $stream->addProcessor("peer", new Peer($stream));
                    break;
                case "seek" :
                    $stream->addProcessor("seek", new Seek($stream));
                    break;
                default :
                    // Let's try and iterate our custom handlers to see if we have anything that matches
                    $processor = $this->getCustomStreamProcessor($inner_header['type']);
                    if ($processor) {
                        $stream->addProcessor($inner_header['type'], new $processor($stream));
                    } else {
                        print ANSI_RED . "Unknown incoming type in line: ".print_r($inner_header, true).ANSI_RESET . "\n";
                        return;
                    }
            }

            print ANSI_YELLOW . "CREATED ".$stream->getType()." STREAM " . ANSI_RESET . "\n";
        }

        // Process already existing stream, make sure end/acks etc are done properly
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
    static public function generate(SwitchBox $switchbox, Node $to, Packet $inner_packet)
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

        return new Packet($header, $body);
    }

}
