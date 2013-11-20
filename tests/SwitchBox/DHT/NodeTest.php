<?php

use SwitchBox\DHT\Node;

//class MockStream extends \SwitchBox\Packet\Line\Stream {
//    public function setId($id) {
//        $this->id = $id;
//    }
//}

class SwitchBox_DHT_NodeTest extends PHPUnit_Framework_TestCase {

    protected $pub = <<< EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsg5qSMpD3Opyz4w0EGe3
hVSR9gAjrg7LOYsImTWp9ZrT56HTFLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYu
yRyfoNbSbHvF2bvMroH4K1e1k/C0fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY
787rqSEuiXgLk1q+9w3XChCvi/HMbIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28
aSxql1OhWxzRCgTrLu52qx5jxBO6lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAP
CfHWQG8qzx+ZYZcupvYjo3xL9RWDlYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXO
jQIDAQAB
-----END PUBLIC KEY-----
EOD;


    function testConstructing() {
        $node = new Node("127.0.0.1", 42424, null, hash("sha256", "foobar"));
        $this->assertEquals($node->getIp(), "127.0.0.1");
        $this->assertEquals($node->getPort(), 42424);
        $this->assertEquals($node->getName(), hash("sha256", "foobar"));
        $this->assertEquals($node->getHash()->getHash(), hash("sha256", "foobar"));
        $this->assertFalse($node->hasPublicKey());

        $this->assertEquals($node->getPingCount(), 0);
        $this->assertCount(0, $node->getStreams());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testConstructingWithException() {
        $node = new Node("127.0.0.1", 42424, null, null);
    }

    function testConstructingWithPubKey() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $this->assertEquals($node->getPublicKey(), $this->pub);
        $this->assertEquals($node->getName(), "552948ace77b08aef5a8c20b6bc5ebefd760440516bb021bcd0f6687ed3c4cf9");
        $this->assertTrue($node->hasPublicKey());
    }

    function testConstructingWithPubKeyAndHash() {
        $node = new Node("127.0.0.1", 42424, $this->pub, "552948ace77b08aef5a8c20b6bc5ebefd760440516bb021bcd0f6687ed3c4cf9");
        $this->assertEquals($node->getPublicKey(), $this->pub);
        $this->assertEquals($node->getName(), "552948ace77b08aef5a8c20b6bc5ebefd760440516bb021bcd0f6687ed3c4cf9");
        $this->assertTrue($node->hasPublicKey());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testConstructingWithPubKeyAndHashException() {
        $node = new Node("127.0.0.1", 42424, $this->pub, "6687ed3c4cf9552948ace77b08aef5a8c20b6bc5ebefd760440516bb021bcd0f");
    }

    function testActivity() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $node->updateActivityTs();
        $this->assertEquals($node->getPingCount(), 0);

        $ts1 = $node->getLastActivityTs();
        sleep(1);
        $node->updateActivityTs();
        $ts2 = $node->getLastActivityTs();

        $this->assertEquals($node->getPingCount(), 0);
        $this->assertGreaterThan($ts1, $ts2);
    }

    function testLines() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $this->assertEmpty($node->getLineIn());
        $this->assertEmpty($node->getLineOut());
        $this->assertFalse($node->isConnected());

        $out_id = bin2hex(openssl_random_pseudo_bytes(16));
        $node->setLineOut($out_id);
        $this->assertEmpty($node->getLineIn());
        $this->assertEquals($node->getLineOut(), $out_id);
        $this->assertFalse($node->isConnected());

        $in_id = bin2hex(openssl_random_pseudo_bytes(16));
        $node->setLineIn($in_id);
        $this->assertEquals($node->getLineIn(), $in_id);
        $this->assertEquals($node->getLineOut(), $out_id);
        $this->assertTrue($node->isConnected());
    }

    public function testOpen() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $node->setOpenAt(12345);
        $this->assertEquals($node->getOpenAt(), 12345);
    }

    public function testEncDecKeys() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $node->setDecryptionKey(12345);
        $this->assertEquals($node->getDecryptionKey(), 12345);

        $node->setEncryptionKey(52352);
        $this->assertEquals($node->getEncryptionKey(), 52352);

    }


    public function testToString() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $this->assertEquals((string)$node, "[552948ace77b08aef5a8c20b6bc5ebefd760440516bb021bcd0f6687ed3c4cf9] 127.0.0.1:42424");
    }

    public function testGetHealthGood() {
        $node = $this->getMockBuilder("\SwitchBox\\DHT\\Node")
                    ->setConstructorArgs(array("127.0.0.1", 42424, null, hash("sha256", "foobar")))
                    ->setMethods(NULL)
                    ->getMock();
        $node->expects($this->any())
                ->method('getLastActivityTs')
                ->will($this->returnValue(time()));

        $this->assertEquals($node->getHealth(), Node::HEALTH_GOOD);
    }

    public function testGetHealthBadAfterIdle() {
        $node = $this->getMockBuilder("\SwitchBox\\DHT\\Node")
                    ->setConstructorArgs(array("127.0.0.1", 42424, null, hash("sha256", "foobar")))
                    ->setMethods(array("getLastActivityTs", "getPingCount"))
                    ->getMock();
        $node->expects($this->any())
                ->method('getLastActivityTs')
                ->will($this->returnValue(time() - Node::MAX_IDLE_TIME * 2));       // Way past max idle time
        $node->expects($this->any())
                ->method('getPingCount')
                ->will($this->returnValue(1));

        $this->assertEquals($node->getHealth(), Node::HEALTH_BAD);
    }


    public function testGetHealthBadAfterPingProbes() {
        $node = $this->getMockBuilder("\SwitchBox\\DHT\\Node")
                    ->setConstructorArgs(array("127.0.0.1", 42424, null, hash("sha256", "foobar")))
                    ->setMethods(array("getLastActivityTs", "getPingCount"))
                    ->getMock();
        $node->expects($this->any())
                ->method('getLastActivityTs')
                ->will($this->returnValue(time() - Node::MAX_IDLE_TIME / 2));       // Still in idle time
        $node->expects($this->any())
                ->method('getPingCount')
                ->will($this->returnValue(Node::MAX_PING_RETRIES * 2));        // But too many probes send

        $this->assertEquals($node->getHealth(), Node::HEALTH_BAD);
    }

    public function testGetHealthUnknown() {
        $node = $this->getMockBuilder("\SwitchBox\\DHT\\Node")
                    ->setConstructorArgs(array("127.0.0.1", 42424, null, hash("sha256", "foobar")))
                    ->setMethods(array("getLastActivityTs", "getPingCount"))
                    ->getMock();
        $node->expects($this->any())
                ->method('getLastActivityTs')
                ->will($this->returnValue(time() - Node::MAX_IDLE_TIME / 2));
        $node->expects($this->any())
                ->method('getPingCount')
                ->will($this->returnValue(Node::MAX_PING_RETRIES - 2));

        $this->assertEquals($node->getHealth(), Node::HEALTH_UNKNOWN);
    }



    public function testInfo() {
        $node = new Node("127.0.0.1", 42424, $this->pub, null);
        $node->setLineIn(12345);
        $node->setLineOut(5252);
        $node->setDecryptionKey(1516);
        $node->setDecryptionKey(75474);
        $info = $node->getInfo();
        $this->assertCount(7, $info);
    }


//    public function testStreams() {
//        $node = new Node("127.0.0.1", 42424, $this->pub, null);
//
//        $stream = $this->getMockBuilder("MockStream", array("getId", "setId"))
//                    ->disableOriginalConstructor()
//                    ->getMock();
//
//        $this->assertNull($node->getStream("1234"));
//
//        $s1 = clone $stream;
//        $s1->setId(1234);
//        $node->addStream($s1);
//        $this->assertEquals($node->getStream("1234")->getId(), $s1->getId());
//
//        $s2 = clone $stream;
//        $s2->setId("5678");
//        $node->addStream($s2);
//        $this->assertEquals($node->getStream("1234"), $s1);
//        $this->assertEquals($node->getStream("5678"), $s2);
//
//        $node->removeStream($s1);
//        $this->assertNull($node->getStream("1234"));
//    }


}
