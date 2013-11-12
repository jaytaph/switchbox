<?php

namespace SwitchBox\Packet\Line;


use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

abstract class streamProcessor {

    /** @var Stream */
    protected $stream;

    function __construct(Stream $stream) {
        $this->setStream($stream);
    }

    abstract function processIncoming(Packet $packet);
    abstract function generate(array $args);

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
