<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Members extends StreamProcessor {

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_YELLOW;
        print_r($header);
        print ANSI_RESET;
    }


    public function generate(array $args)
    {
        print "*** generate MEMBERS\n";

        $header = $this->getStream()->createOutStreamHeader('_members', array('_' => $args), false);
        print_r($header);
        return new Packet($this->getSwitchBox(), $header, null);
    }

}
