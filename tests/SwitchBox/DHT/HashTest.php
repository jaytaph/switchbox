<?php

use SwitchBox\DHT\Hash;

class SwitchBox_DHT_HashTest extends PHPUnit_Framework_TestCase {

    function testHashConstructing() {
        $hash = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b");   // secret
    }

    static function incorrectHashes() {
        return array(
            array(""),
            array("0"),
            array("8843d7f92416211de9ebb963ff4ce28125932"),
            array("2bXX0d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a2XX"),
            array(true),
        );
    }

    /**
     * @dataProvider incorrectHashes
     * @expectedException InvalidArgumentException
     */
    function testHashConstructingErrors($hash) {
        $tmp = new Hash($hash);
    }

    function testGetHash() {
        $hash = new Hash("c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");   // foobar
        $this->assertEquals($hash->gethash(), "c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");
        $this->assertEquals($hash->gethash(Hash::OUTPUT_HEX), "c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");
        $this->assertEquals($hash->gethash(Hash::OUTPUT_BINARY), hex2bin("c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2"));
    }

    function testEquals() {
        $hash1 = new Hash("c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");
        $hash2 = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b");

        $this->assertTrue($hash1->equals($hash1));
        $this->assertTrue($hash2->equals($hash2));
        $this->assertFalse($hash1->equals($hash2));
        $this->assertFalse($hash2->equals($hash1));
    }

    function testBinaryOr() {
        $hash1 = new Hash("c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");      // foobar
        $hash2 = new Hash("c3Fb8f213F20e8Fd9047df39466b3c8974ff92c2fa383d4a3960710000f0ffff");
        $hash3 = new Hash("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF");
        $hash4 = new Hash("0000000000000000000000000000000000000000000000000000000000000000");
        $hash5 = new Hash("8080808080808080808080808080808080808080808080808080808080808080");
        $hash6 = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b");      // secret

        $this->assertEquals($hash1->binaryXor($hash1)->getHash(), "0000000000000000000000000000000000000000000000000000000000000000");
        $this->assertEquals($hash1->binaryXor($hash2)->getHash(), "005000d0080000500000020000000000001a0000000000000000004cae003b0d");
        $this->assertEquals($hash1->binaryXor($hash3)->getHash(), "3c54700ec8df17526fb822c6b994c3768b1a6d3d05c7c2b5c69f8eb3510f3b0d");
        $this->assertEquals($hash1->binaryXor($hash4)->getHash(), "c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");
        $this->assertEquals($hash1->binaryXor($hash5)->getHash(), "432b0f71b7a0682d10c75db9c6ebbc09f46512427ab8bdcab9e0f1cc2e704472");
        $this->assertEquals($hash1->binaryXor($hash6)->getHash(), "e81382a24c3d4b4e1b94de58ecee6a0fc905780f8b5ac3bc9b3f98375bd766a9");
    }

    /**
     *
     */
    function testCompare() {
        $hash1 = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b");
        $hash2 = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a200");

        $this->assertEquals($hash1->compare($hash1), 0);
        $this->assertEquals($hash2->compare($hash2), 0);
        $this->assertLessThan($hash2->compare($hash1), 0);
        $this->assertGreaterThan($hash1->compare($hash2), 0);
        $this->assertLessThan($hash2->compare($hash1), 0);
        $this->assertGreaterThan($hash1->compare($hash2), 0);
    }

    function testDistance() {
        $hash1 = new Hash("c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2");      // foobar
        $hash2 = new Hash("c3Fb8f213F20e8Fd9047df39466b3c8974ff92c2fa383d4a3960710000f0ffff");
        $hash3 = new Hash("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF");
        $hash4 = new Hash("0000000000000000000000000000000000000000000000000000000000000000");
        $hash5 = new Hash("8080808080808080808080808080808080808080808080808080808080808080");
        $hash6 = new Hash("2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b");      // secret

        $this->assertEquals($hash1->distance($hash2), 246);
        $this->assertEquals($hash1->distance($hash3), 253);
        $this->assertEquals($hash1->distance($hash4), 255);
        $this->assertEquals($hash1->distance($hash5), 254);
        $this->assertEquals($hash1->distance($hash6), 255);

        $this->assertEquals($hash1->distance($hash1), -1);
        $this->assertEquals($hash2->distance($hash1), $hash1->distance($hash2));
    }

}
