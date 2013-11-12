<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Chat extends streamProcessor {

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_YELLOW;
        print_r($header);
        print ANSI_RESET;
    }


    public function generate(array $args)
    {
        print "*** generate CHAT\n";

        $header = $this->getStream()->createOutStreamHeader('_chat', array('_' => $args), false);
        print_r($header);
        return new Packet($this->getSwitchBox(), $header, null);
    }

}
