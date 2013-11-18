<?php

namespace SwitchBox\Packet;

use SwitchBox\Packet;
use SwitchBox\SwitchBox;

abstract class PacketHandler {

    /** @var SwitchBox */
    protected $switchbox;

    /**
     *
     * @param SwitchBox $switchbox
     */
    public function __construct(SwitchBox $switchbox) {
        $this->switchbox = $switchbox;
    }

    /**
     * Return switchbox
     *
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    /**
     * Actual handler for this type of packet
     *
     * @param Packet $packet
     * @return mixed
     */
    abstract public function process(Packet $packet);
}
