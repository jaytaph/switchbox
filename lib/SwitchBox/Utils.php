<?php

namespace SwitchBox;

class Utils {

    static function hex2bin($str) {
        if (function_exists( 'hex2bin')) {
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
