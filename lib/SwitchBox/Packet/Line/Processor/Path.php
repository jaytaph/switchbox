<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\Packet;
use SwitchBox\Packet\Line\Channel;

class Path extends ChannelProcessor {

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_BLUE;
        print "*** process Path ***\n";
        print_r($header);
        print ANSI_RESET;
    }


    public function generate(array $args)
    {
    }

}
