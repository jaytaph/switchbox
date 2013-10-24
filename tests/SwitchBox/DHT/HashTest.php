<?php

use SwitchBox\DHT\Hash;

class SwitchBox_DHT_HashTest extends PHPUnit_Framework_TestCase {

    function testHashConstructing() {
        $hash = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");
    }

    static function incorrectHashes() {
        return array(
            array(""),
            array("0"),
            array("8843d7f92416211de9ebb963ff4ce28125932"),
            array("XX43d7f92416211de9ebb963ff4ce281259328XX"),
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
        $hash = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");   // foobar
        $this->assertEquals($hash->gethash(), "8843d7f92416211de9ebb963ff4ce28125932878");
        $this->assertEquals($hash->gethash(Hash::OUTPUT_HEX), "8843d7f92416211de9ebb963ff4ce28125932878");
        $this->assertEquals($hash->gethash(Hash::OUTPUT_BINARY), hex2bin("8843d7f92416211de9ebb963ff4ce28125932878"));
    }

    function testEquals() {
        $hash1 = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");
        $hash2 = new Hash("8843d7f92416211de9ebb963ff4ce28125932800");

        $this->assertTrue($hash1->equals($hash1));
        $this->assertTrue($hash2->equals($hash2));
        $this->assertFalse($hash1->equals($hash2));
        $this->assertFalse($hash2->equals($hash1));
    }

    function testBinaryOr() {
        $hash1 = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");      // foobar
        $hash2 = new Hash("8843d7f02416011de0ebb960000ce20005000800");
        $hash3 = new Hash("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF");
        $hash4 = new Hash("0000000000000000000000000000000000000000");
        $hash5 = new Hash("8080808080808080808080808080808080808080");
        $hash6 = new Hash("e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4");      // secret

        $this->assertEquals($hash1->binaryXor($hash1)->getHash(), "0000000000000000000000000000000000000000");
        $this->assertEquals($hash1->binaryXor($hash2)->getHash(), "000000090000200009000003ff40008120932078");
        $this->assertEquals($hash1->binaryXor($hash3)->getHash(), "77bc2806dbe9dee21614469c00b31d7eda6cd787");
        $this->assertEquals($hash1->binaryXor($hash4)->getHash(), "8843d7f92416211de9ebb963ff4ce28125932878");
        $this->assertEquals($hash1->binaryXor($hash5)->getHash(), "08c35779a496a19d696b39e37fcc6201a513a8f8");
        $this->assertEquals($hash1->binaryXor($hash6)->getHash(), "6daa2de28708ec0701a4cca9550badbb43ac2d8c");
    }

    /**
     *
     */
    function testCompare() {
        $hash1 = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");
        $hash2 = new Hash("8843d7f92416211de9ebb963ff4ce28125932800");

        $this->assertEquals($hash1->compare($hash1), 0);
        $this->assertEquals($hash2->compare($hash2), 0);
        $this->assertLessThan($hash2->compare($hash1), 0);
        $this->assertGreaterThan($hash1->compare($hash2), 0);
        $this->assertLessThan($hash2->compare($hash1), 0);
        $this->assertGreaterThan($hash1->compare($hash2), 0);
    }

    function testDistance() {
        $hash1 = new Hash("8843d7f92416211de9ebb963ff4ce28125932878");      // foobar
        $hash2 = new Hash("8843d7f92416211de9ebb963ff4ce28125932876");
        $hash3 = new Hash("8843d7f92416211de9ebb963ff4ce28125932858");
        $hash4 = new Hash("0000000000000000000000000000000000000000");
        $hash5 = new Hash("884Fd7f92416211de9ebb963ff4ce28125932878");
        $hash6 = new Hash("e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4");      // secret

        $this->assertEquals($hash1->distance($hash2), 99);
        $this->assertEquals($hash1->distance($hash3), 101);
        $this->assertEquals($hash1->distance($hash4), 255);
        $this->assertEquals($hash1->distance($hash5), 243);
        $this->assertEquals($hash1->distance($hash6), 254);

        $this->assertEquals($hash1->distance($hash1), -1);
        $this->assertEquals($hash2->distance($hash1), $hash1->distance($hash2));
    }

}
