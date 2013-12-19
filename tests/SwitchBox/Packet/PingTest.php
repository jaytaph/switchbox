<?php

use SwitchBox\DHT\KeyPair;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\Ping;
use SwitchBox\SwitchBox;

include_once "PacketBase.php";

class SwitchBox_Packet_PingTest extends PacketBase {

    function testPingProcessGenerate() {
        $node = new Node("1.1.1.1", 42424, $this->their_pub, null);
        $packet = Ping::generate($this->my_sb, $node, "");

        $this->assertCount(0, $packet->getheader());
        $this->assertNull($packet->getBody());
    }

    function testPingProcess() {
        $node = new Node("1.1.1.1", 42424, $this->their_pub, null);
        $packet = Ping::generate($this->my_sb, $node, "");

        $ph = new Ping($this->my_sb);
        $this->assertEmpty($ph->process($packet));

    }



}
