<?php

namespace SwitchBox\Packet;

use SwitchBox\Packet;
use SwitchBox\SwitchBox;

abstract class PacketHandler {

    /** @var SwitchBox */
    protected $switchbox;

    function __construct(SwitchBox $switchbox) {
        $this->switchbox = $switchbox;
    }

    /**
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    abstract public function process(Packet $packet);
}
