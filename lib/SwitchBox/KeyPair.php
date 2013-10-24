<?php

namespace SwitchBox;

/*
 * Not much to see here, but we could add PEM2DER conversions, loading/saving keys, maybe generating etc..
 */


class KeyPair {
    protected $private_key;
    protected $public_key;

    function __construct($private_key, $public_key)
    {
        $this->private_key = $private_key;
        $this->public_key = $public_key;
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
