<?php

namespace SwitchBox\Packet;

use SwitchBox\Packet;
use SwitchBox\Seed;
use SwitchBox\SwitchBox;

interface iProcessor {

    static function process(SwitchBox $switchbox, Packet $packet);
    static function generate(SwitchBox $switchbox, Seed $seed, $family = null);

}
