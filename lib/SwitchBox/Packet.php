<?php

/*
 * Packet abstraction
 */

namespace SwitchBox;

use SwitchBox\Packet\Line;

class Packet {
    /** @var array */
    protected $header = array();
    /** @var string */
    protected $body = null;
    /** @var  string    One of the TYPE_* constants */
    protected $type;
//    protected $processor = null;

    protected $timestamp;

    protected $from_ip;
    protected $from_port;

    const TYPE_UNKNOWN  = "unknown";
    const TYPE_OPEN     = "open";
    const TYPE_LINE     = "line";
    const TYPE_PING     = "ping";

    /**
     * @param Switchbox $switchbox
     * @param null $header
     * @param null $body
     */
    public function __construct(Switchbox $switchbox, $header = null, $body = null) {
        $this->switchbox = $switchbox;

        if ($header !== null) $this->setHeader($header);
        if ($body !== null) $this->setBody($body);

        $this->timestamp = time();
    }

    // @TODO: A packet should not concern themselves on where they came from. But might link a packet to a stream/node
    public function setFrom($ip, $port) {
        $this->from_ip = $ip;
        $this->from_port = $port;
    }

    /**
     * @return array
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @param array $header
     */
    public function setHeader(array $header) {
        $this->header = $header;
        $this->type = self::TYPE_UNKNOWN;

        if (count($this->header) == 0) {
            // Empty JSON, this is a PING packet
            $this->type = self::TYPE_PING;
            return;
        }

        if (! isset($this->header['type'])) {
            // No type set
            return;
        }

        // Check packet and set correct processor
        switch ($this->header['type']) {
            case "open" :
                $this->type = self::TYPE_OPEN;
//                $this->processor = new Line($this->switchbox, $this);
                break;
            case "line" :
                $this->type = self::TYPE_LINE;
//                $this->processor = new Line($this->switchbox, $this);
                break;
        }
    }

    /**
     * @return mixed
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @param $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * @param SwitchBox $switchbox
     * @param $bindata
     * @param null $ip
     * @param null $port
     * @return Packet
     */
    static function decode(SwitchBox $switchbox, $bindata, $ip = null, $port = null) {
        $res = unpack('nlen', substr($bindata, 0, 2));
        $json = substr($bindata, 2, $res['len']);
        $body = substr($bindata, 2 + $res['len']);
        $packet = new Packet($switchbox, json_decode($json, true), $body);

        // set packet's originating IP and port number
        if ($ip && $port) {
            $packet->setFrom($ip, $port);
        }

        return $packet;
    }

    /**
     * @return string
     */
    function encode() {
        $json = json_encode($this->getHeader());
        $len = strlen($json);

        return pack("nA".$len."A*", $len, $json, $this->getBody());
    }

    function getType($as_string = false) {
        if ($as_string) {
            // Return constants as string. They are for now the same
            return $this->type;
        }
        return $this->type;
    }

    function getFromIp() {
        return $this->from_ip;
    }

    function getFromPort() {
        return $this->from_port;
    }

}
