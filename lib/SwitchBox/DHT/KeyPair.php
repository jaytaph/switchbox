<?php

namespace SwitchBox\DHT;

class KeyPair {
    protected $private_key;
    protected $public_key;

    const FORMAT_PEM    = "pem";
    const FORMAT_DER    = "der";


    /**
     * @param $filename
     */
    public function __construct($filename, $generate = true)
    {
        // Generate new keypair if we can't find one
        if (! file_exists($filename) && $generate) {
            $json = json_decode(self::generate($filename));
        } else {
            $json = json_decode(file_get_contents($filename));
        }

        $this->private_key = $json->private;
        $this->public_key = $json->public;
    }


    /**
     * @param $filename
     */
    static public function generate($filename, $bits = 2048) {
        $res = openssl_pkey_new(array(
            "digest_algo" => "sha512",
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));

        $tmp = openssl_pkey_get_details($res);
        $keypair['public'] = $tmp['key'];
        openssl_pkey_export($res, $keypair['private']);

        $json = json_encode($keypair);
        file_put_contents($filename, $json);

        // return json, so even if writing fails, we at least have some keys to work with...
        return $json;
    }


    /**
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->private_key;
    }


    /**
     * @return mixed
     */
    public function getPublicKey($format = self::FORMAT_PEM)
    {
        if ($format == self::FORMAT_DER) {
            return self::convertPemToDer($this->public_key);
        }
        return $this->public_key;
    }


    /**
     * Return the DER length of a string
     *
     * @param $length
     * @return string
     */
    static public function derLength($length) {
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
    static public function convertPemToDer($pem) {
        $matches = array();
        if (!preg_match('~^-----BEGIN ([A-Z ]+)-----\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----END \1-----\s*$~D', $pem, $matches)) {
            throw new \RuntimeException('Invalid PEM format encountered: '.$pem."\n");
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
    static public function convertDerToPem($der, $header = "PUBLIC KEY") {
        $pem = chunk_split(base64_encode($der), 64, "\n");
        $pem = "-----BEGIN ".$header."-----\n".$pem."-----END ".$header."-----\n";
        return $pem;
    }

}
