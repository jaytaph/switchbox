<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;

interface iLineProcessor {

    static function process(SwitchBox $switchbox, Node $node, Packet $packet);
}
