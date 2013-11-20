<?php

use SwitchBox\DHT\Bucket;
use SwitchBox\DHT\Node;

class SwitchBox_DHT_BucketTest extends PHPUnit_Framework_TestCase {

    function testConstructing() {
        $bucket = new Bucket();
        $this->assertEquals($bucket->getEvictions(), 0);
    }

    function testGetIterator() {
        $bucket = new Bucket();
        $this->assertInstanceOf("Traversable", $bucket->getIterator());
    }

    function testGetCount() {
        $bucket = new Bucket();
        $this->assertCount(0, $bucket);
        $this->assertCount(0, $bucket->getIterator());

        $bucket->addNode(new Node("127.0.0.1", 42424, null, hash("sha256", "foobar")));

        $this->assertCount(1, $bucket);
        $this->assertCount(1, $bucket->getIterator());

        $bucket->addNode(new Node("127.0.0.1", 42424, null, hash("sha256", "foobar")));
        $this->assertCount(2, $bucket);
        $this->assertCount(2, $bucket->getIterator());
    }

    function testIsFull() {
        $bucket = new Bucket();
        $this->assertFalse($bucket->isFull());

        for ($i=0; $i!=8; $i++) {
            $bucket->addNode(new Node("127.0.0.1", 42424, null, hash("sha256", "foobar")));
        }
        $this->assertTrue($bucket->isFull());
    }

    function testIsEvictions() {
        $node = $this->getMockBuilder("\SwitchBox\\DHT\\Node")
                    ->setConstructorArgs(array("127.0.0.1", 42424, null, hash("sha256", "foobar")))
                    ->getMock();

        // Don't change this seed!
        srand(12345);

        $node->expects($this->any())
                ->method('getHealth')
                ->will($this->returnCallback(
                    function(){
                        $i = rand(1, 10);
                        if ($i < 5) return Node::HEALTH_GOOD;
                        if ($i < 8) return Node::HEALTH_UNKNOWN;
                        return Node::HEALTH_BAD;
                    }
                ));

        $bucket = new Bucket();

        for ($i=0; $i!=45; $i++) {
            $cnode = clone $node;
            $bucket->addNode($cnode);
        }
        $this->assertEquals($bucket->getEvictions(), 38);
        $this->assertEquals(count($bucket), 6);
        $this->assertFalse($bucket->isFull());

    }





}
