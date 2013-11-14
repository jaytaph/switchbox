<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Custom extends StreamProcessor {

    protected $custom_handlers = array();

    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_BLUE;
        print_r($header);

        print "Our processors: ";
        print_r(array_keys($this->custom_handlers));
        print ANSI_RESET;
    }


    function addCustomHandler($type, callable $processor) {
        $this->custom_handlers[$type] = $processor;
    }


    public function generate(array $args)
    {
    }

}
