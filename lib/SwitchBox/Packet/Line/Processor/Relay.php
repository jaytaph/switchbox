<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\Packet;
use SwitchBox\Packet\Line\Channel;

class Relay extends ChannelProcessor {

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_WHITE;
        print "*** process Relay ***\n";
        print_r($header);
        print ANSI_RESET;
    }


    public function generate(array $args)
    {
    }

}
