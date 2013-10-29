<?php

namespace SwitchBox;

class Utils {

    static function bin2hex($str) {
        return bin2hex($str);
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

    /**
     * Return the DER length of a string
     *
     * @param $length
     * @return string
     */
    static function derLength($length) {
        if ($length < 128) return str_pad(dechex($length), 2, '0', STR_PAD_LEFT);
        $output = dechex($length);
        if (strlen($output) % 2 != 0) $output = '0'.$output;
        return dechex(128 + strlen($output)/2) . $output;
    }

    /**
     * Convert a PEM string to DER
     *
     *
     * @TODO: Shouldn't this be ASN1?
     *
     * @param $pem
     * @return string
     */
    static function convertPemToDer($pem) {
        $matches = array();
        if (!preg_match('~^-----BEGIN ([A-Z ]+)-----\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----END \1-----\s*$~D', $pem, $matches)) {
            die('Invalid PEM format encountered.'."\n");
        }
        $derData = str_replace(array("\r", "\n"), array('', ''), $matches[2]);
        $derData = base64_decode($derData);
        return $derData;
    }

    /**
     * Convert a DER string to PEM
     *
     * @param $der
     * @param string $header
     * @return string
     */
    static function convertDerToPem($der, $header = "PUBLIC KEY") {
        $pem = chunk_split(base64_encode($der), 64, "\n");
        $pem = "-----BEGIN ".$header."-----\n".$pem."-----END ".$header."-----\n";
        return $pem;
    }


    static function isHex($str) {
        if (strspn($str, '0123456789abcdefABCDEF') != strlen($str)) return false;
        return true;
    }
}
