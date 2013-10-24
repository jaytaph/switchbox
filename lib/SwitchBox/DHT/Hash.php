<?php
/**
 * Hash functionality. Only does SHA256 for now
 */

namespace SwitchBox\DHT;

use SwitchBox\Utils;

class Hash {
    const HASH_SIZE = 64;                   // Assuming SHA256 hashes, so 64 bytes

    const OUTPUT_BINARY     = 1;            // Binary output (0x255 0x52 0x52 ...)
    const OUTPUT_HEX        = 2;            // hex output as string: "1235ABC ...")
    const OUTPUT_BYTE_ARRAY = 3;            // Binary output in reversed array format (MSB first)

    protected $hash;                        // actual hash as hex-string

    function __construct($hash) {
        if (! $this->isHash($hash)) {
            throw new \InvalidArgumentException("Hash is not a valid SHA1 hash");
        }
        $this->hash = $hash;
    }

    /**
     * Returns hash in specified format
     *
     * @param int $format
     * @return array|string
     */
    function getHash($format = Hash::OUTPUT_HEX) {
        if ($format == Hash::OUTPUT_BINARY) {
            return hex2bin($this->hash);
        }

        if ($format == Hash::OUTPUT_BYTE_ARRAY) {
            $tmp = array();
            for ($i=0; $i!=strlen($this->hash); $i++) {
                $tmp[] = hexdec($this->hash[$i]);
            }
            return array_reverse($tmp);
        }

        return $this->hash;
    }

    /**
     * Returns true when given data is actually a valid hash
     *
     * @param $hash
     * @return bool
     */
    static function isHash($hash) {
        if (strlen($hash) != self::HASH_SIZE) return false;
        return Utils::isHex($hash);
    }

    /**
     * Returns distance between 2 hashes. -1 when equal. 255 furthest bit, 0 closest bit
     * @param Hash $other
     * @return int
     */
    function distance(Hash $other) {
        $sbtab = array(-1,0,1,1,2,2,2,2,3,3,3,3,3,3,3,3);
        $ret = 252;

        $s = $this->getHash(Hash::OUTPUT_BYTE_ARRAY);
        $d = $other->getHash(Hash::OUTPUT_BYTE_ARRAY);

        for ($i=count($s)-1; $i>=0; $i--) {
            $diff = $s[$i] ^ $d[$i];

            if ($diff) {
                return $ret + $sbtab[$diff];
            }
            $ret -= 4;
        }
        return -1;
    }


    /**
     * Compare hashes. 0 = equal. < 0 self is larger > 0 other is larger\
     *
     * @param Hash $other
     * @return int
     */
    function compare(Hash $other) {
        $s = $this->getHash(Hash::OUTPUT_BYTE_ARRAY);
        $d = $other->getHash(Hash::OUTPUT_BYTE_ARRAY);

        for ($i=count($s)-1; $i>=0; $i--) {
            // We probably should not return directly, but check all digits anyway against timing attacks.
            if ($s[$i] != $d[$i]) return ($d[$i] - $s[$i]);
        }
        return 0;
    }

    /**
     * Binary XOR two hashes, and return the result as another hash
     *
     * @param Hash $other
     * @return Hash
     */
    function binaryxor(Hash $other) {
        $s = $this->getHash(Hash::OUTPUT_BYTE_ARRAY);
        $d = $other->getHash(Hash::OUTPUT_BYTE_ARRAY);

        $r = "";
        for ($i=count($s)-1; $i >=0; $i--) {
            $r .= dechex($s[$i] ^ $d[$i]);
        }
        return new Hash($r);
    }


    /**
     * Returns true when the two hashes are equal, false otherwise
     *
     * @param Hash $other
     * @return bool
     */
    function equals(Hash $other) {
        return $this->compare($other) == 0;
    }


    /**
     * @return string
     */
    function __toString() {
        return $this->getHash(Hash::OUTPUT_HEX);
    }

}
