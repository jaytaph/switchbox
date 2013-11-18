<?php

namespace SwitchBox\DHT;

use phpecc\EcDH;
use phpecc\NISTcurve;
use phpecc\Point;
use phpecc\PrivateKey;
use phpecc\PublicKey;
use phpecc\Utilities\Gmp;
use SwitchBox\Packet\Line\Stream;
use SwitchBox\Utils;

class Node {
    // Health status
    const HEALTH_GOOD    = "good";
    const HEALTH_BAD     = "bad";
    const HEALTH_UNKNOWN = "unknown";

    const MAX_ACTIVITY_TIME      = 10;      // Maximum amount of seconds past since last activity when this node is considered a good node.
    const MAX_IDLE_TIME          = 30;      // Number of seconds in which we must receive at least SOME activity.
    const MAX_PING_RETRIES       = 3;       // Number of pings we send out. If we hit this value, the node is considered a bad node.

    /** @var Hash */
    protected $hash;                        // Hash object for this node
    /** @var string */
    protected $name;                        // Hex string of hash / nodename
    /** @var string */
    protected $public_key;                  // Public key of the node in PEM format
    /** @var string */
    protected $ip;                          // IP that connected
    /** @var int */
    protected $port;                        // Port that connected

    /** @var string */
    protected $line_in = null;              // Line in string
    /** @var string */
    protected $line_out= null;              // Line out string

    /** @var \StdClass */
    protected $ecc_our_keypair;             // Our generated ECC public + private key
    /** @var PublicKey */
    protected $ecc_their_pubkey;            // Their communicated ECC public key

    /** @var string */
    protected $encryption_key;              // Line encryption key
    /** @var string */
    protected $decryption_key;              // Line decryption key

    /** @var Stream[] */
    protected $streams = array();           // Array of currently running streams for this node

    /** @var int */
    protected $open_at;

    /** @var int */
    protected $last_activity_ts;            // Timestamp of last RX or TX activity
    /** @var int */
    protected $ping_count;                  // Number of pings send sinds last inbound activity


    /**
     * @param $ip
     * @param $port
     * @param null $public_key
     * @param null $hash
     * @throws \InvalidArgumentException
     */
    public function __construct($ip, $port, $public_key = null, $hash = null) {
        // Either public key or hash must be set
        if (! $public_key && ! $hash) {
            throw new \InvalidArgumentException("Either public key or hash must be filled");
        }

        // If we have a public key
        if ($public_key) {
            $pubkey_hash = self::generateNodeName($public_key);

            // Sanity check to see if publickey and hash match
            if ($pubkey_hash != $hash && $hash != null) {
                throw new \InvalidArgumentException("Hash does not match public key!");
            }
            $this->setName($pubkey_hash);
            $this->setPublicKey($public_key);
        }

        // Set the hash if needed
        if ($hash != null) {
            $this->setName($hash);
        }

        // We always have the node's IP and port
        $this->setIp($ip);
        $this->setPort($port);

        // Init our vars
        $this->ping_count = 0;
        $this->last_activity_ts = time();
        $this->streams = array();
    }

    public function updateActivityTs() {
        // Update activity timestamp
        $this->last_activity_ts = time();

        // Also reset ping count
        $this->ping_count = 0;
    }

    public function getLastActivityTs() {
        return $this->last_activity_ts;
    }


    /**
     * @param $public_key
     * @return string
     */
    static public function generateNodeName($public_key)
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

    public function addStream(Stream $stream) {
        print  "*** Adding stream: ".$stream->getId()."\n";
        $this->streams[$stream->getId()] = $stream;
    }

    public function removeStream(Stream $stream) {
        print  "*** Removing stream: ".$stream->getId()."\n";
        unset($this->streams[$stream->getId()]);
    }

    /**
     * @return Stream[]
     */
    public function getStreams() {
        return $this->streams;
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
     * @return \StdClass
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


    public function recalcEncryptionKeys() {
        // No need to (re)calc when not both lines are known
        if (! $this->getLineIn() || ! $this->getLineOut()) return;

        // Derive secret key
        $curve = NISTcurve::generator_256();
        $alice = $this->getEccOurKeyPair();
        $bob = PublicKey::decode($curve, Utils::bin2hex($this->getEccTheirPubKey(), 130));

        $ecDH = new EcDH($curve);
        $ecDH->setPublicPoint($bob->getPoint());
        $ecdhe = $ecDH->getDerivedSharedSecret($alice->privkey->getSecretMultiplier());


        // Hash everything into an encode and decode key
        $ctx = hash_init('sha256');
        hash_update($ctx, Utils::hex2bin(GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, Utils::hex2bin($this->getLineOut()));
        hash_update($ctx, Utils::hex2bin($this->getLineIn()));
        $key = hash_final($ctx, true);
        $this->setEncryptionKey($key);

        $ctx = hash_init('sha256');
        hash_update($ctx, Utils::hex2bin(GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, Utils::hex2bin($this->getLineIn()));
        hash_update($ctx, Utils::hex2bin($this->getLineOut()));
        $key = hash_final($ctx, true);
        $this->setDecryptionKey($key);
    }



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

    public function getHealth()
    {
        /** @var $node Node */
        $idle = time() - $this->getLastActivityTs();

        // Last activity in the last 30 seconds, good node
        if ($idle < self::MAX_ACTIVITY_TIME) return self::HEALTH_GOOD;

        // Over an hour, but we haven't sent out at least 3 pings to them: unknown node
        if ($idle < self::MAX_IDLE_TIME && $this->getPingCount() < self::MAX_PING_RETRIES) {
            // @TODO: Should we sent out another ping probe here??
            return self::HEALTH_UNKNOWN;
        }

        // Everything else is a bad node
        return self::HEALTH_BAD;
    }


    /**
     * Return number of times we have sent out a ping reply
     * @return int
     */
    public function getPingCount() {
        return $this->ping_count;
    }


    /**
     * Return information about this node
     *
     * @return array
     */
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


    /**
     * String representation of this node object
     *
     * @return string
     */
    public function __toString() {
        return "[".$this->getName()."] ".$this->getIp().":".$this->getPort();
    }

}
