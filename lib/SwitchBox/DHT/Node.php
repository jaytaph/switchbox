<?php

namespace SwitchBox\DHT;

use phpecc\EcDH;
use phpecc\NISTcurve;
use phpecc\Point;
use phpecc\PrivateKey;
use phpecc\PublicKey;
use phpecc\Utilities\Gmp;
use SwitchBox\KeyPair;
use SwitchBox\Stream;
use SwitchBox\Utils;

class Node {
    protected $hash;               // Hash object for this node
    protected $name;               // Hex string of hash / nodename
    protected $public_key;         // Public key of the node in PEM format
    protected $ip;                 // IP that connected
    protected $port;               // Port that connected

    protected $line_in = null;     // Line in string
    protected $line_out= null;     // Line out string

    /** @var PublicKey */
    protected $ecc_our_keypair;     // Our generated ECC public + private key
    protected $ecc_their_pubkey;    // Their communicated ECC public key

    protected $encryption_key;              // Line encryption key
    protected $decryption_key;              // Line decryption key

    protected $streams = array();           // Array of currently running streams for this node

    protected $open_at;

    /**
     * @return mixed
     */
    public function getHash()
    {
        if ($this->hash) return $this->hash;

        $this->hash = new Hash($this->name);
        return $this->hash;
    }




    public function hasPublicKey() {
        return ! empty($this->public_key);
    }

    /**
     * @param mixed $open_at
     */
    public function setOpenAt($open_at)
    {
        $this->open_at = $open_at;
    }

    /**
     * @return mixed
     */
    public function getOpenAt()
    {
        return $this->open_at;
    }



    function __construct($ip, $port, $public_key = null, $hash = null) {

        if (! $public_key && ! $hash) {
            throw new \InvalidArgumentException("Either public key or hash must be filled");
        }

        if ($public_key) {
            $pubkey_hash = self::generateNodeName($public_key);
            if ($pubkey_hash != $hash && $hash != null) {
                throw new \InvalidArgumentException("Hash does not match public key!");
            }
            $this->setName($pubkey_hash);
            $this->setPublicKey($public_key);
        }

        if ($hash != null) {
            $this->setName($hash);
        }

        $this->setIp($ip);
        $this->setPort($port);

        $this->streams = array();
    }


    /**
     * @param $public_key
     * @return string
     */
    public static function generateNodeName($public_key)
    {
        return hash('sha256', KeyPair::convertPemToDer($public_key));
    }

    /**
     * Nodename, as generated by the public key
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->public_key;
    }

    /**
     * @param mixed $public_key
     */
    public function setPublicKey($public_key)
    {
        $this->public_key = $public_key;
    }


    /**
     * @param $id
     * @return Stream|null
     */
    public function getStream($id)
    {
        return isset($this->streams[$id]) ? $this->streams[$id] : null;
    }

    function addStream(Stream $stream) {
        $this->streams[$stream->getId()] = $stream;
    }

    function removeStream(Stream $stream) {
        unset($this->streams[$stream->getId()]);
    }


    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }


    /**
     * @param mixed $line_in
     */
    public function setLineIn($line_in)
    {
        $this->line_in = $line_in;
    }

    /**
     * @return mixed
     */
    public function getLineIn()
    {
        return $this->line_in;
    }

    /**
     * @param mixed $line_out
     */
    public function setLineOut($line_out)
    {
        $this->line_out = $line_out;
    }

    /**
     * @return mixed
     */
    public function getLineOut()
    {
        return $this->line_out;
    }



    public function isConnected() {
        return ($this->line_out && $this->line_in);
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @param mixed $decryption_key
     */
    public function setDecryptionKey($decryption_key)
    {
        $this->decryption_key = $decryption_key;
    }

    /**
     * @return mixed
     */
    public function getDecryptionKey()
    {
        return $this->decryption_key;
    }

    /**
     * @param mixed $encryption_key
     */
    public function setEncryptionKey($encryption_key)
    {
        $this->encryption_key = $encryption_key;
    }

    /**
     * @return mixed
     */
    public function getEncryptionKey()
    {
        return $this->encryption_key;
    }

    /**
     * @param mixed $ecc_their_pubkey
     */
    public function setEccTheirPubkey($ecc_their_pubkey)
    {
        $this->ecc_their_pubkey = $ecc_their_pubkey;
    }

    /**
     * @return mixed
     */
    public function getEccTheirPubkey()
    {
        return $this->ecc_their_pubkey;
    }

    /**
     * @return \phpecc\PublicKey
     */
    public function getEccOurKeypair()
    {
        if ($this->ecc_our_keypair == null) {
            $g = NISTcurve::generator_256();
            $n = $g->getOrder();

            $secret = Gmp::gmp_random($n);
            $secretG = Point::mul($secret, $g);

            $this->ecc_our_keypair = new \StdClass();
            $this->ecc_our_keypair->pubkey = new PublicKey($g, $secretG);
            $this->ecc_our_keypair->privkey = new PrivateKey($this->ecc_our_keypair->pubkey, $secret);
        }

        return $this->ecc_our_keypair;
    }


    public function getInfo() {
        return array(
            "Hash" => $this->getName(),
            "Connected" => $this->isConnected() ? "yes" : "no",
            "IP:Port" => $this->getIp().":".$this->getPort(),
            "Line In" => $this->getLineIn(),
            "Line Out " => $this->getLineOut(),
            "Dec Key" => Utils::bin2hex($this->getDecryptionKey(),64),
            "Enc Key" => Utils::bin2hex($this->getEncryptionKey(),64),
        );
    }


//    public function addToBucket($bucketIdx, Node $node) {
//        if (! isset($this->buckets[$bucketIdx])) {
//            $this->buckets[$bucketIdx] = array();
//        }
//        $this->buckets[$bucketIdx][] = $node;
//        $node->setBucket($node, $bucketIdx);
//    }
//
//    function setBucket($bucketIdx) {
//        $this->bucket_idx = $bucketIdx;
//    }
//
//    function getBucket() {
//        return $this->bucket_idx;
//    }


    function __toString() {
        return $this->getIp().":".$this->getPort()." [".$this->getName()."]";
    }


    function recalcEncryptionKeys() {
        // No need to (re)calc when not both lines are known
        if (! $this->getLineIn() || ! $this->getLineOut()) return;

        // Derive secret key
        $curve = \phpecc\NISTcurve::generator_256();
        $alice = $this->getEccOurKeyPair();
        $bob = \phpecc\PublicKey::decode($curve, Utils::bin2hex($this->getEccTheirPubKey(), 130));

        $ecDH = new EcDH($curve);
        $ecDH->setPublicPoint($bob->getPoint());
        $ecdhe = $ecDH->getDerivedSharedSecret($alice->privkey->getSecretMultiplier());


        // Hash everything into an encode and decode key
        $ctx = hash_init('sha256');
        hash_update($ctx, Utils::hex2bin(\phpecc\Utilities\GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, Utils::hex2bin($this->getLineOut()));
        hash_update($ctx, Utils::hex2bin($this->getLineIn()));
        $key = hash_final($ctx, true);
        $this->setEncryptionKey($key);

        $ctx = hash_init('sha256');
        hash_update($ctx, Utils::hex2bin(\phpecc\Utilities\GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, Utils::hex2bin($this->getLineIn()));
        hash_update($ctx, Utils::hex2bin($this->getLineOut()));
        $key = hash_final($ctx, true);
        $this->setDecryptionKey($key);
    }

}
