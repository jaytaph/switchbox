<?php

namespace SwitchBox\Packet\Line\Processor;


use SwitchBox\Packet;
use SwitchBox\Packet\Line\Stream;
use SwitchBox\SwitchBox;

abstract class StreamProcessor {

    /** @var Stream */
    protected $stream;


    abstract public function processIncoming(Packet $packet);
    abstract public function generate(array $args);


    /**
     * @param $stream
     */
    function __construct(Stream $stream)
    {
        $this->stream = $stream;
    }


    /**
     * @return mixed
     */
    public function getNode()
    {
        return $this->getStream()->getTo();
    }


    /**
     * @param mixed $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
    }


    /**
     * @return mixed
     */
    public function getStream()
    {
        return $this->stream;
    }


    /**
     * @return SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->getStream()->getSwitchBox();
    }

}
