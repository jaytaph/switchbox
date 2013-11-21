<?php

use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class SwitchBox_UtilsTest extends PHPUnit_Framework_TestCase {

    function testBin2Hex() {
        $this->assertEquals(Utils::bin2hex("\x3\x4", 6), "000304");
        $this->assertEquals(Utils::bin2hex("\x3\x4", 4), "0304");
        $this->assertEquals(Utils::bin2hex("\x1\x2\x3\x4", 2), "01020304");
    }


    public function h2bprovider() {
        return array(
            array("304", "\x3\x4"),
            array("0304", "\x3\x4"),
            array("0000000304", "\x0\x0\x0\x3\x4"),
            array("000000304", "\x0\x0\x0\x3\x4"),
            array("010304", "\x1\x3\x4"),
            array("100304", "\x10\x3\x4"),
        );
    }

    /**
     * @dataProvider h2bprovider
     */
    function testHex2Bin($a, $b) {
        //$this->assertEquals(Utils::hex2bin($a), $b);
        //$this->assertEquals(Utils::_hex2bin($a), $b);
    }

}
