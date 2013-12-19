<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Line\Processor\Connect;
use SwitchBox\Packet\Line\Processor\Peer;
use SwitchBox\Packet\Line\Processor\Seek;
use SwitchBox\Packet\Line\Processor\ChannelProcessor;
use SwitchBox\Packet\Line\Channel;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class Line extends PacketHandler {

    /** @var ChannelProcessor[] */
    protected $channel_processors = array();         // All the channel processors that are available


    /**
     * Add a custom channel processor
     *
     * @param $type
     * @param $class
     * @throws \InvalidArgumentException
     */
    public function addCustomChannelProcessor($type, $class) {
        $tmp = new $class(null);
        if (! $tmp instanceof ChannelProcessor) {
            throw new \InvalidArgumentException("Class must be an instance of ChannelProcessor!");
        }

        $this->channel_processors[$type] = $class;
    }


    /**
     * @param $type
     * @return null|ChannelProcessor
     */
    public function getCustomChannelProcessor($type) {
        if (isset($this->channel_processors[$type])) {
            return $this->channel_processors[$type];
        }
        return null;
    }


    /**
     * Process a line packet by decoding and passing it to the correct channel-handler
     *
     * @param Packet $packet
     * @return mixed|void
     * @throws \InvalidArgumentException
     */
    public function process(Packet $packet)
    {
        $header = $packet->getHeader();

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

        // We received a packet, update TS
        $from->updateActivityTs();

        // Decrypt line body with inner packet
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($from->getDecryptionKey());
//        print "Decrypting with IV/KEY: ".$header['iv']." / ".bin2hex($from->getDecryptionKey())."\n";
        $inner_packet = Packet::decode($cipher->decrypt($packet->getBody()));

        $inner_header = $inner_packet->getHeader();
        $channel = $from->getChannel($inner_header['c']);
        if (! $channel) {
            $channel = new Channel($this->getSwitchBox(), $from, $inner_header['c']);

            // There is an incoming request. We must respond to it
            print "No channel found. Creating new channel...\n";

            // Channel hasn't been opened yet. Let's create a channel
            switch ($inner_header['type']) {
                case "connect" :
                    $channel->addProcessor("connect", new Connect($channel));
                    break;
                case "peer" :
                    $channel->addProcessor("peer", new Peer($channel));
                    break;
                case "seek" :
                    $channel->addProcessor("seek", new Seek($channel));
                    break;
                default :
                    // Let's try and iterate our custom handlers to see if we have anything that matches
                    $processor = $this->getCustomChannelProcessor($inner_header['type']);
                    if ($processor) {
                        $channel->addProcessor($inner_header['type'], new $processor($channel));
                    } else {
                        print ANSI_RED . "Unknown incoming type in line: ".print_r($inner_header, true).ANSI_RESET . "\n";
                        return;
                    }
            }

            print ANSI_YELLOW . "CREATED ".$channel->getType()." CHANNEL " . ANSI_RESET . "\n";
        }

        // Process already existing channel, make sure end/acks etc are done properly
        $channel->process($inner_packet);
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
