<?php

/*
 * Packet abstraction
 */

namespace SwitchBox;

use SwitchBox\Packet\Open;

class Packet {
    /** @var array */
    protected $header;
    /** @var string */
    protected $body;
    /** @var  string    One of the TYPE_* constants */
    protected $type;
    protected $processor = null;

    protected $timestamp;

    protected $from_ip;
    protected $from_port;

    const TYPE_UNKNOWN  = "unknown";
    const TYPE_OPEN     = "open";
    const TYPE_LINE     = "line";
    const TYPE_PING     = "ping";

    /**
     * @param null $header
     * @param null $body
     */
    function __construct(SwitchBox $switchbox, $header, $body) {
        $this->switchbox = $switchbox;

        $this->setHeader($header);
        $this->setBody($body);

        $this->timestamp = time();
    }

    function setFrom($ip, $port) {
        $this->from_ip = $ip;
        $this->from_port = $port;
    }

    /**
     * @return array
     */
    function getHeader() {
        return $this->header;
    }

    /**
     * @param array $header
     */
    function setHeader(array $header) {
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
                $this->processor = new Open($this->switchbox, $this);
                break;
            case "line" :
                $this->type = self::TYPE_OPEN;
                $this->processor = new Line($this->switchbox, $this);
                break;
        }
    }

    /**
     * @return mixed
     */
    function getBody() {
        return $this->body;
    }

    /**
     * @param $body
     */
    function setBody($body) {
        $this->body = $body;
    }

    /**
     * @param $bindata
     * @return Packet
     */
    static function decode(SwitchBox $switchbox, $bindata) {
        $res1  = unpack('nlen/A*rest', $bindata);
        $res2 = unpack('A'.$res1['len'].'json/A*body', $res1['rest']);
        return new Packet($switchbox, json_decode($res2['json'], true), $res2['body']);
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

    function __toString() {
        return "<" . strlen(json_encode($this->getHeader())) . ">\n" .
               print_r($this->getHeader(),true) . "\n" .
               "Body[".strlen($this->getBody())."]";
    }

}
