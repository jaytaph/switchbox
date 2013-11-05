<?php

namespace SwitchBox;

class Utils {

    static function bin2hex($str, $pad_length) {
        return str_pad(bin2hex($str), $pad_length, "0", STR_PAD_RIGHT);
    }

    static function hex2bin($str) {
        if (function_exists( 'hex2bin')) {
            // When hash starts with leading 0's it gets cut away by PHP during string casting. Hurrah!
            while (strlen($str) < 32) $str = "0" . $str;

            // If we have a 33 byte string (or higher), we assume it's a 64byte hash.
            while (strlen($str) > 32 && strlen($str) < 64) $str = "0" . $str;
            return hex2bin($str);
        }

        // Pre 5.4 doesn't have hex2bin
        $sbin = "";
        $len = strlen( $str );
        for ($i=0; $i<$len; $i+=2) {
            $sbin .= pack("H*", substr($str, $i, 2 ));
        }
        return $sbin;
    }



    static function isHex($str) {
        if (strspn($str, '0123456789abcdefABCDEF') != strlen($str)) return false;
        return true;
    }
}
