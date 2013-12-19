<?php

namespace SwitchBox\Packet\Line\Processor;


use SwitchBox\Packet;
use SwitchBox\Packet\Line\Channel;
use SwitchBox\SwitchBox;

abstract class ChannelProcessor {

    /** @var Channel */
    protected $channel;


    abstract public function processIncoming(Packet $packet);
    abstract public function generate(array $args);


    /**
     * @param $channel
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }


    /**
     * @return mixed
     */
    public function getNode()
    {
        return $this->getChannel()->getTo();
    }


    /**
     * @param mixed $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }


    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }


    /**
     * @return SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->getChannel()->getSwitchBox();
    }

}
