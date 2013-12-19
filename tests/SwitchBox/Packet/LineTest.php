<?php

use SwitchBox\DHT\KeyPair;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\Ping;
use SwitchBox\SwitchBox;

include_once "PacketBase.php";

class SwitchBox_Packet_LineTest extends PacketBase {

    function testOpenProcessGenerate() {
    //        // Create open packet
    //        $node = new Node("1.1.1.1", 42424, $this->their_pub, null);
    //        $packet = Open::generate($this->my_sb, $node, "");
    //        $ph = new Open($this->their_sb);
    //        $node = $ph->process($packet);
    //
    //        $this->assertNotNull($node);
    //        $this->assertTrue($node->isConnected());
    //
    //
    //        $node = new Node("2.2.2.2", 42424, $this->my_pub, null);
    //        $packet = Open::generate($this->their_sb, $node, "");
    //        $ph = new Open($this->my_sb);
    //        $node = $ph->process($packet);
    //
    //        $this->assertNotNull($node);
    //        $this->assertTrue($node->isConnected());
    }
}
