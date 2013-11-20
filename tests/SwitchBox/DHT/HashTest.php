<?php

use SwitchBox\DHT\Hash;

class SwitchBox_DHT_HashTest extends PHPUnit_Framework_TestCase {

    function testHashConstructing() {
        $hash = new Hash(hash("sha256", "foobar"));
        $this->assertEquals($hash->getHash(), hash("sha256", "foobar"));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testHashConstructingException() {
        $hash = new Hash("foobar");
    }

    function testGetHash() {
        $hash = new Hash(hash("sha256", "foobar"));
        $this->assertEquals($hash->getHash(Hash::OUTPUT_HEX), hash("sha256", "foobar"));

        $this->assertEquals($hash->getHash(Hash::OUTPUT_BINARY), hash("sha256", "foobar", true));

        $arr = $hash->getHash(Hash::OUTPUT_BYTE_ARRAY);
        $this->assertCount(64, $arr);
        $this->assertEquals($arr[0], 2);
        $this->assertEquals($arr[10], 1);
        $this->assertEquals($arr[20], 8);
        $this->assertEquals($arr[30], 4);
        $this->assertEquals($arr[40], 9);
    }

    function testCompare() {
        $hash1 = new Hash(hash("sha256", "foobar"));
        $hash2 = new Hash(hash("sha256", "baz"));

        $this->assertEquals($hash1->compare($hash1), 0);
        $this->assertEquals($hash2->compare($hash2), 0 );
        $this->assertGreaterThan($hash1->compare($hash2), 0);
        $this->assertLessThan($hash2->compare($hash1), 0);
    }

    function testBinaryXor() {
        $hash1 = new Hash(hash("sha256", "foobar"));
        $hash2 = new Hash(hash("sha256", "baz"));

        $this->assertEquals($hash1->binaryXor($hash2)->getHash(), "790e2f677a13c8565081741b526f6f4125db308842c5383d3a2875e5c9d44464");
    }

    function distanceProvider() {
        return array(
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "cdb80b529e7ba4c058e58baf14ba1291056045e8759d71d171679431f66827e4", 255),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "c943673ed22bb96bafdf4806890d016f97c09c7cf2bc1b1692e4aaaaf8953a85", 255),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "7364913c962d98383b0aa696711ce5be67d7f5cd5e0c022ab08c118b1b25670b", 241),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "7364336bef1d492caf5644708b75fa418784790f327d78243fbc5e6f38ecb141", 241),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "732c1118ba85942e8f991495bef2ec37de0061bc4b8ca790c780ca7ad8235715", 246),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "532ec0ecf47050fe4ae8eb2d848efe767cfe9c1ffc7ddc0381d1277681f2e56d", 253),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "153c82fb9267ea071f41914cad2a8e8441be17ba17bdc2d2c3374001a533eb78", 254),
            array("736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", "736711cf55ff95fa967aa980855a0ee9f7af47d6287374a8cd65e1a36171ef08", 0),
        );
    }


    /**
     * @dataProvider distanceProvider
     */
    function testDistanceId($h1, $h2, $distance) {
        $hash1 = new Hash($h1);
        $hash2 = new Hash($h2);
        $this->assertEquals($hash1->getDistanceId($hash2), $distance);
        $this->assertEquals($hash2->getDistanceId($hash1), $distance);
    }

    function testEquals() {
        $hash1 = new Hash(hash("sha256", "foobar"));
        $hash2 = new Hash(hash("sha256", "baz"));

        $this->assertTrue($hash1->equals($hash1));
        $this->assertTrue($hash2->equals($hash2));
        $this->assertFalse($hash1->equals($hash2));
        $this->assertFalse($hash2->equals($hash1));
    }

    function testToString() {
        $hash1 = new Hash(hash("sha256", "foobar"));
        $hash2 = new Hash(hash("sha256", "baz"));

        $this->assertEquals((string)$hash1, "c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");
        $this->assertEquals((string)$hash2, "baa5a0964d3320fbc0c6a922140453c8513ea24ab8fd0577034804a967248096");
    }


}
