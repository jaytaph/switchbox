<?php

namespace SwitchBox;

/*
 * Not much to see here, but we could add PEM2DER conversions, loading/saving keys, maybe generating etc..
 */


class KeyPair {
    protected $private_key;
    protected $public_key;


    /**
     * @param $filename
     */
    function __construct($filename, $generate)
    {
        // Generate new keypair if we can't find one
        if (! file_exists($filename) && $generate) {
            $json = self::generate("seed.json");
        } else {
            $json = json_decode(file_get_contents($filename));
        }

        $this->private_key = $json->private;
        $this->public_key = $json->public;
    }


    /**
     * @param $filename
     */
    static function generate($filename) {
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
    public function getPublicKey()
    {
        return $this->public_key;
    }

}
