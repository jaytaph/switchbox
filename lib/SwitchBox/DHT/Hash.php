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

    /** @var string */
    protected $hash;                        // actual hash as hex-string


    /**
     * @param $hash
     * @throws \InvalidArgumentException
     */
    public function __construct($hash) {
        if (strlen($hash) < self::HASH_SIZE) $hash = str_pad($hash, 64, '0', STR_PAD_RIGHT);

        if (! $this->isHash($hash)) {
            throw new \InvalidArgumentException("Hash '".$hash."' is not a valid SHA256 hash");
        }
        $this->hash = $hash;
    }


    /**
     * Returns hash in specified format
     *
     * @param int $format
     * @return array|string
     */
    public function getHash($format = self::OUTPUT_HEX) {
        if ($format == self::OUTPUT_BINARY) {
            return Utils::hex2bin($this->hash);
        }

        if ($format == self::OUTPUT_BYTE_ARRAY) {
            $tmp = array();
            $len = strlen($this->hash);
            for ($i=0; $i!=$len; $i++) {
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
    static public function isHash($hash) {
        if (strlen($hash) != self::HASH_SIZE) return false;
        return Utils::isHex($hash);
    }


    /**
     * Compare hashes. 0 = equal. < 0 self is larger > 0 other is larger\
     *
     * @param Hash $other
     * @return int
     */
    public function compare(Hash $other) {
        $s = $this->getHash(self::OUTPUT_BYTE_ARRAY);
        $d = $other->getHash(self::OUTPUT_BYTE_ARRAY);

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
    public function binaryXor(Hash $other) {
        $s = gmp_init($this->getHash(), 16);
        $d = gmp_init($other->getHash(), 16);
        $r = gmp_xor($s, $d);
        return new Hash(gmp_strval($r, 16));
    }


    /**
     * This is just a bit quicker way of fetching xor + prefixlen.
     *
     * @param Hash $other
     * @return int
     */
    public function getDistanceId(Hash $other) {
        $s = gmp_init($this->getHash(), 16);
        $d = gmp_init($other->getHash(), 16);
        $r = gmp_xor($s, $d);

        $d = 256;
        while ($d > 0 && ! gmp_testbit($r, $d)) $d--;
        return $d;
    }


    /**
     * Returns true when the two hashes are equal, false otherwise
     *
     * @param Hash $other
     * @return bool
     */
    public function equals(Hash $other) {
        return $this->compare($other) == 0;
    }


    /**
     * Returns string representation of the hash
     *
     * @return string
     */
    public function __toString() {
        return $this->getHash(self::OUTPUT_HEX);
    }

}
