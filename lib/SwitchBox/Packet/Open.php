<?php

namespace SwitchBox\Packet;

use SwitchBox\DHT\Node;
use SwitchBox\DHT\KeyPair;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;
use SwitchBox\Packet;
use SwitchBox\Packet\Line\Channel;

class Open extends PacketHandler {

    /**
     * Process an open packet
     *
     * @param Packet $packet
     * @return null|Node
     * @throws \DomainException
     */
    public function process(Packet $packet) {
        $header = $packet->getHeader();
        if ($header['type'] != "open") {
            throw new \DomainException("Not an OPEN packet");
        }

        if (! isset($header['open'])) {
            throw new \DomainException("Missing OPEN value");
        }

        $open = base64_decode($header['open']);
        openssl_private_decrypt($open, $eccpubkey, $this->getSwitchBox()->getKeyPair()->getPrivateKey(), OPENSSL_PKCS1_OAEP_PADDING);
        if (! $eccpubkey) {
            throw new \DomainException("couldn't decrypt open");
        }

        if (strlen($packet->getBody()) == 0) {
            throw new \DomainException("body missing on open");
        }

        $hash = hash('sha256', $eccpubkey, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($hash);
        $body = $cipher->decrypt($packet->getBody());

        $innerPacket = Packet::decode($body);

        $hash = hash('sha256', $this->getSwitchBox()->getKeyPair()->getPublicKey(KeyPair::FORMAT_DER));
        $innerHeader = $innerPacket->getHeader();
        if ($innerHeader['to'] != $hash) {
            throw new \DomainException("open for wrong hashname");
        }

        if (strlen($innerHeader['line']) != 32 || ! Utils::isHex($innerHeader['line'])) {
            throw new \DomainException("invalid line id contained");
        }

        if (strlen($innerPacket->getBody()) == 0) {
            throw new \DomainException("open missing attached key");
        }

        $hash = hash('sha256', $innerPacket->getBody(), true);

        $key = $innerPacket->getBody();

        $res = openssl_pkey_get_public(KeyPair::convertDerToPem($key));
        if (! $res) {
            print ANSI_RED . "Error while getting the public key!\n";
            $n = KeyPair::convertDerToPem($key);
            print_r($n);
            print ANSI_RESET;
            return null;
        }
        $details = openssl_pkey_get_details($res);
        if (! $details) {
            throw new \DomainException("not a valid public key!");
        }
        if ($details['type'] != OPENSSL_KEYTYPE_RSA) {
            throw new \DomainException("public key is not a RSA key");
        }
        if ($details['bits'] < 2048) {
            throw new \DomainException("public key must be at least 2048 bits");
        }


        // Decrypt signature
        $ctx = hash_init('sha256');
        hash_update($ctx, $eccpubkey);
        hash_update($ctx, Utils::hex2bin($innerHeader['line']));
        $aes_key = hash_final($ctx, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(Utils::hex2bin($header['iv']));
        $cipher->setKey($aes_key);
        $dec_sig = $cipher->encrypt(base64_decode($header['sig']));

        if (openssl_verify($packet->getBody(), $dec_sig, $res, "sha256") != 1) {
            throw new \DomainException("invalid signature");
        }

        // Do we know this node or not?
        $node = $this->getSwitchBox()->getMesh()->getNode(Utils::bin2hex($hash, 64));
        if (! $node) {
            // New node, let's create it
            $node = new Node($packet->getFromIp(), $packet->getFromPort(), KeyPair::convertDerToPem($key), hash('sha256', $innerPacket->getBody()));
            $this->getSwitchBox()->getMesh()->addNode($node);
        }

        // We also found the public key, so set it.
        $node->setPublicKey(KeyPair::convertDerToPem($key));

        $node->setEccTheirPubKey($eccpubkey);

        if ($innerHeader['at'] < $node->getOpenAt()) {
            throw new \DomainException("invalid at found");
        }

        // Update open time values
        $node->setOpenAt(time());

        // we have an open line to the other side..
        $node->setLineIn($innerHeader['line']);
        $node->recalcEncryptionKeys();


        if ($node->isConnected()) {
            print ANSI_GREEN."Finalized connection with ".(string)$node."!!!!!".ANSI_RESET."\n";
            print_r($node->getInfo());

            // Try and do a seek to ourselves, this allows us to find our outside IP/PORT
            $channel = new Channel($this->getSwitchBox(), $node);
            $channel->addProcessor("seek", new Line\Processor\Seek($channel));
            $channel->start(array(
                'hash' => $this->getSwitchBox()->getSelfNode()->getName(),
            ));
        } else {
            print ANSI_YELLOW."Node ".(string)$node." is not yet connected. ".ANSI_RESET."\n";
            $this->getSwitchBox()->send($node, self::generate($this->getSwitchBox(), $node, null));
        }

        return $node;
    }



    /**
     * Generate a new open packet to the given node. Optionally tag with $family
     *
     * @param SwitchBox $switchbox
     * @param Node $node
     * @param null $family
     * @return Packet
     * @throws \DomainException
     */
    static public function generate(SwitchBox $switchbox, Node $node, $family = null) {
        // Create a random lineout
        $node->setLineOut(Utils::bin2hex(openssl_random_pseudo_bytes(16), 32));
        $node->recalcEncryptionKeys();

        // Verify given public key
        self::_verifyKey($node->getPublicKey());

        // 2.create IV
        $iv = openssl_random_pseudo_bytes(16);

        // 4. SHA256 hash ECC key
        $hash = hash('sha256', Utils::hex2bin($node->getEccOurKeypair()->pubkey->encode()), true);

        $inner_packet = self::_createInnerPacket($switchbox->getKeyPair()->getPublicKey(KeyPair::FORMAT_DER), $node, $family);


        // 6. Encrypt inner packet
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv($iv);
        $cipher->setKey($hash);
        $body = $cipher->encrypt($inner_packet->encode());


        // 7. Create sig
        openssl_sign($body, $sig, $switchbox->getKeyPair()->getPrivateKey(), "sha256");

        $ctx = hash_init('sha256');
        hash_update($ctx, Utils::hex2bin($node->getEccOurKeypair()->pubkey->encode()));
        hash_update($ctx, Utils::hex2bin($node->getLineOut()));
        $aes_key = hash_final($ctx, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv($iv);
        $cipher->setKey($aes_key);
        $aes = $cipher->encrypt($sig);

        $sig = base64_encode($aes);

        // 8. Create open param
        openssl_public_encrypt(Utils::hex2bin($node->getEccOurKeypair()->pubkey->encode()), $open, $node->getPublicKey(), OPENSSL_PKCS1_OAEP_PADDING);
        $open = base64_encode($open);

        // 9. Form outer packet
        $header = array(
            'type' => 'open',
            'open' => $open,
            'iv' => Utils::bin2hex($iv, 32),
            'sig' => $sig,
        );

        return new Packet($header, $body);
    }

    static protected function _createInnerPacket($public_key, Node $node, $family = "")
    {
        // 5. Form inner packet
        $header = array(
            'to' => $node->getName(),
            'at' => floor(microtime(true) * 1000),
            'line' => $node->getLineOut(),
        );
        if ($family) {
            $header['family'] = $family;
        }

        return new Packet($header, $public_key);
    }


    /**
     * Verifies the public key in order to make sure it's valid.
     *
     * @param $key
     * @throws \DomainException
     */
    static protected function _verifyKey($key) {
        // 1. Verify public key
        $res = openssl_pkey_get_public($key);
        if (! $res) {
            throw new \DomainException("Error while getting the public key!");
        }

        print ANSI_GREEN . "\n";
        print_r($key);
        print ANSI_RESET;

        $details = openssl_pkey_get_details($res);

        if (! $details) {
            throw new \DomainException("not a valid public key!");
        }
        if ($details['type'] != OPENSSL_KEYTYPE_RSA) {
            throw new \DomainException("public key is not a RSA key");
        }
        if ($details['bits'] < 2048) {
            throw new \DomainException("public key must be at least 2048 bits");
        }
    }

}
