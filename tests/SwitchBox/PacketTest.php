<?php

use SwitchBox\Packet;
use SwitchBox\SwitchBox;

class SwitchBox_PacketTest extends PHPUnit_Framework_TestCase {

    function testConstructing() {
        $packet = new Packet(null, null);
        $this->assertEmpty($packet->getHeader());
        $this->assertEmpty($packet->getBody());
    }

    function testIpp() {
        $packet = new Packet(null, null);
        $packet->setFrom("127.0.0.1", 12345);
        $this->assertEquals($packet->getFromIp(), "127.0.0.1");
        $this->assertEquals($packet->getFromPort(), 12345);
    }

    function testHeader() {
        $packet = new Packet(array("foo" => "bar"), null);
        $this->assertArrayHasKey("foo", $packet->getHeader());
        $this->assertArrayNotHasKey("bar", $packet->getHeader());
    }
    function testBody() {
        $packet = new Packet(array("foo" => "bar"), "foobar");
        $this->assertEquals($packet->getBody(), "foobar");
    }

    function testPacketTypes() {
        $packet = new Packet(array(), null);
        $this->assertEquals($packet->getType(), Packet::TYPE_PING);
        $this->assertEquals($packet->getType(true), "ping");

        $packet = new Packet(array("type" => Packet::TYPE_LINE), null);
        $this->assertEquals($packet->getType(), Packet::TYPE_LINE);
        $this->assertEquals($packet->getType(true), "line");
    }

    function testPacketDecoding() {
        $packet = new Packet(array("type" => "line", "foo" => "bar"), "foobar");
        $bin = $packet->encode();

        $packet = Packet::decode($bin);
        $header = $packet->getHeader();
        $this->assertEquals($header['type'], "line");
        $this->assertEquals($header['foo'], "bar");
        $this->assertEquals($packet->getBody(), "foobar");

        $packet = Packet::decode($bin, "192.168.1.1", 443);
        $this->assertEquals($packet->getFromIp(), "192.168.1.1");
        $this->assertEquals($packet->getFromPort(), 443);
    }



}
