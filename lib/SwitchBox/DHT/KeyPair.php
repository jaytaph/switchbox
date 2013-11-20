<?php

namespace SwitchBox\DHT;

class KeyPair {
    const FORMAT_PEM    = "pem";
    const FORMAT_DER    = "der";

    /** @var string */
    protected $private_key;             // PEM formatted private key
    /** @var string */
    protected $public_key;              // PEM formatted public key


    /**
     * @param $priv
     * @param $pub
     */
    public function __construct($priv, $pub)
    {
        $this->public_key = $pub;
        $this->private_key = $priv;
    }


    /**
     * Generate new json file with keypair
     *
     * @param $filename
     * @param bool $generate
     * @param int $bits
     * @throws \InvalidArgumentException
     * @return KeyPair
     */
    static public function fromFile($filename, $generate = true, $bits = 2048) {
        if (! file_exists($filename)) {
            if (! $generate) {
                throw new \InvalidArgumentException("Cannot find key file: $filename\n");
            }
            $kp = self::generate($bits);
            file_put_contents($filename, json_encode(array("public" => $kp->getPublicKey(), "private" => $kp->getPrivateKey())));
            return $kp;
        }

        $json = file_get_contents($filename);
        $key = json_decode($json);
        return new KeyPair($key->private, $key->public);
    }

    /**
     * @param int $bits
     * @return KeyPair
     */
    static public function generate($bits = 2048) {
        $res = openssl_pkey_new(array(
            "digest_algo" => "sha512",
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));

        $tmp = openssl_pkey_get_details($res);
        $keypair['public'] = $tmp['key'];
        openssl_pkey_export($res, $keypair['private']);

        return new KeyPair($keypair['private'], $keypair['public']);
    }


    /**
     * Return private part of the key
     *
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->private_key;
    }


    /**
     * Return public part of the key in specified format
     *
     * @param string $format
     * @return mixed
     */
    public function getPublicKey($format = self::FORMAT_PEM)
    {
        if ($format == self::FORMAT_DER) {
            return self::convertPemToDer($this->public_key);
        }
        return $this->public_key;
    }


//    /**
//     * Return the DER length of a string
//     *
//     * @param $length
//     * @return string
//     */
//    static public function derLength($length) {
//        if ($length < 128) return str_pad(dechex($length), 2, '0', STR_PAD_LEFT);
//        $output = dechex($length);
//        if (strlen($output) % 2 != 0) $output = '0'.$output;
//        return dechex(128 + strlen($output)/2) . $output;
//    }


    /**
     * Convert a PEM string to DER
     * @TODO: Shouldn't this be ASN1?
     *
     * @param $pem
     * @throws \RuntimeException
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
