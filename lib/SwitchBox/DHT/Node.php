<?php

namespace SwitchBox\DHT;

use phpecc\PublicKey;

class Node {
    /** @var Hash */
    protected $hash;

    protected $open_packet_sent = false;    // True when an open packet has been sent to this node

    protected $open_at = 0;                 // Time open packet has been send
    protected $recv_at;                     // Time received (last) packet
    protected $pubkey;                      // Public key of the node
    protected $ip;                          // IP that connected
    protected $port;                        // Port that connected
    protected $line_in;                     // Line in string
    protected $line_out;                    // Line out string

    /** @var PublicKey */
    protected $ecc;                         // Our generated ECC public key

    protected $encryption_key;              // Line encryption key
    protected $decryption_key;              // Line decryption key

    protected $buckets = array();           // @TODO needed here?
    protected $bucket_idx = null;           // @TODO needed here?


    function __construct(Hash $hash) {
        $this->hash = $hash;
        $this->buckets = array();
    }

    /**
     * @param mixed $pubkey
     */
    public function setPubkey($pubkey)
    {
        $this->pubkey = $pubkey;
    }

    /**
     * @return mixed
     */
    public function getPubkey()
    {
        return $this->pubkey;
    }

    public function setOpenAt($timestamp)
    {
        $this->open_at = $timestamp;
    }

    public function getOpenAt() {
        return $this->open_at;
    }

    public function getAt()
    {
        return $this->at;
    }

    public function setHash(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return Hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
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

    /**
     * @param boolean $open_packet_sent
     */
    public function setSentOpenPacket($open_packet_sent)
    {
        $this->open_packet_sent = $open_packet_sent;
    }

    /**
     * @return boolean
     */
    public function hasSentOpenPacket()
    {
        return $this->open_packet_sent;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $recv_at
     */
    public function setRecvAt($recv_at)
    {
        $this->recv_at = $recv_at;
    }

    /**
     * @return mixed
     */
    public function getRecvAt()
    {
        return $this->recv_at;
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
     * @param \stdClass $ecc
     */
    public function setEcc(\stdClass $ecc)
    {
        $this->ecc = $ecc;
    }

    /**
     * @return stdClass
     */
    public function getEcc()
    {
        return $this->ecc;
    }




    public function addToBucket($bucketIdx, Node $node) {
        if (! isset($this->buckets[$bucketIdx])) {
            $this->buckets[$bucketIdx] = array();
        }
        $this->buckets[$bucketIdx][] = $node;
        $node->setBucket($node, $bucketIdx);
    }

    function setBucket($bucketIdx) {
        $this->bucket_idx = $bucketIdx;
    }

    function getBucket() {
        return $this->bucket_idx;
    }


    function __toString() {
        return $this->getIp().":".$this->getPort()."[".$this->getHash()."]";
    }

}