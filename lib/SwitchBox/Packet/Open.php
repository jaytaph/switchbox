<?php

namespace SwitchBox\Packet;

use phpecc\EcDH;
use phpecc\NISTcurve;
use phpecc\Point;
use phpecc\PrivateKey;
use phpecc\PublicKey;
use phpecc\Utilities\Gmp;
use SwitchBox\DHT\Hash;
use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Seed;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class Open implements iProcessor {

    static function process(SwitchBox $switchbox, Packet $packet) {
        $header = $packet->getHeader();
        if ($header['type'] != "open") {
            throw new \InvalidArgumentException("Not an OPEN packet");
        }

        if (! isset($header['open'])) {
            throw new \InvalidArgumentException("Missing OPEN value");
        }

        $open = base64_decode($header['open']);
        openssl_private_decrypt($open, $decrypted, $switchbox->getKeyPair()->getPrivateKey(), OPENSSL_PKCS1_OAEP_PADDING);
        if (! $decrypted) {
            throw new \InvalidArgumentException("couldn't decrypt open");
        }

        if (strlen($packet->getBody()) == 0) {
            throw new \InvalidArgumentException("body missing on open");
        }

        $hash = hash('sha256', $decrypted, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(hex2bin($header['iv']));
        $cipher->setKey($hash);
        $body = $cipher->decrypt($packet->getBody());

        $innerPacket = Packet::decode($switchbox, $body);

        $my_node = hash('sha256', Utils::convertPemToDer($switchbox->getKeyPair()->getPublicKey()));
        $innerHeader = $innerPacket->getHeader();
        if ($innerHeader['to'] != $my_node) {
            throw new \InvalidArgumentException("open for wrong hashname");
        }

        if (strlen($innerHeader['line']) != 32 || ! Utils::isHex($innerHeader['line'])) {
            throw new \InvalidArgumentException("invalid line id contained");
        }

        if (strlen($innerPacket->getBody()) == 0) {
            throw new \InvalidArgumentException("open missing attached key");
        }

        $hash = hash('sha256', $innerPacket->getBody(), true);

        $key = $innerPacket->getBody();
        $res = openssl_pkey_get_public(Utils::convertDerToPem($key));
        $details = openssl_pkey_get_details($res);
        if (! $details) {
            throw new \LogicException("not a valid public key!");
        }
        if ($details['type'] != OPENSSL_KEYTYPE_RSA) {
            throw new \LogicException("public key is not a RSA key");
        }
        if ($details['bits'] < 2048) {
            throw new \LogicException("public key must be at least 2048 bits");
        }


        // Decrypt signature
        $ctx = hash_init('sha256');
        hash_update($ctx, $decrypted);
        hash_update($ctx, hex2bin($innerHeader['line']));
        $aes_key = hash_final($ctx, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv(hex2bin($header['iv']));
        $cipher->setKey($aes_key);
        $dec_sig = $cipher->encrypt(base64_decode($header['sig']));

        if (openssl_verify($packet->getBody(), $dec_sig, $res, "sha256") != 1) {
            throw new \InvalidArgumentException("invalid signature");
        }

        $from = $switchbox->getMesh()->seen(bin2hex($hash));
        if (! $from) {
            // @TODO: add to mesh?? How about our line and secrets?
            die ("No idea who '".bin2hex($hash)."' is.. :(\n");
            //$from = new Node(new Hash(bin2hex($hash)));
        }

        if ($innerHeader['at'] < $from->getOpenAt()) {
            throw new \InvalidArgumentException("invalid at found");
        }

        // Update values
        $from->setOpenAt($innerHeader['at']);
        $from->setPubKey($key);
        $from->setIp($packet->getFromIp());
        $from->setPort($packet->getFromPort());
        $from->setRecvAt(time());

        if ($from->getLineIn() != $innerHeader['line']) {
            $from->setSentOpenPacket(false);
        }

        if (! $from->hasSentOpenPacket()) {
            // @TODO: Sent an open packet to the NODE $from
        }


        // we have an open line to the other side..
        $from->setLineIn($innerHeader['line']);

        // Derive secret key
        $curve = \phpecc\NISTcurve::generator_256();
        $bob = \phpecc\PublicKey::decode($curve, bin2hex($decrypted));
        /** @var $alice \phpecc\PrivateKey */
        $ecc = $from->getEcc();
        $alicePriv = $ecc->privkey;

        $ecDH = new EcDH($curve);
        $ecDH->setPublicPoint($bob->getPoint());
        $ecdhe = $ecDH->getDerivedSharedSecret($alicePriv->getSecretMultiplier());


        // Hash everything into an encode and decode key
        $ctx = hash_init('sha256');
        hash_update($ctx, hex2bin(\phpecc\Utilities\GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, hex2bin($from->getLineOut()));
        hash_update($ctx, hex2bin($from->getLineIn()));
        $key = hash_final($ctx, true);
        $from->setEncryptionKey($key);
        print "EK: ".bin2hex($key)."\n";

        $ctx = hash_init('sha256');
        hash_update($ctx, hex2bin(\phpecc\Utilities\GMP::gmp_dechex($ecdhe)));
        hash_update($ctx, hex2bin($from->getLineIn()));
        hash_update($ctx, hex2bin($from->getLineOut()));
        $key = hash_final($ctx, true);
        $from->setDecryptionKey($key);
        print "DK: ".bin2hex($key)."\n";

        print $from;

        print "\n";
    }


    static function generate(SwitchBox $switchbox, Seed $seed, $family = null) {
        // 0. Setup some stuff
        $to = new \StdClass();

        $to->rsaPubKey = $seed->getPublicKey();
        $to->hash = $seed->getHash();
        $to->line = bin2hex(openssl_random_pseudo_bytes(16));

        $to_node = new Node(new Hash($to->hash));
        $to_node->setLineIn($to->line);

        // 1. Verify public key
        $res = openssl_pkey_get_public($to->rsaPubKey);
        $details = openssl_pkey_get_details($res);

        if (! $details) {
            throw new \LogicException("not a valid public key!");
        }
        if ($details['type'] != OPENSSL_KEYTYPE_RSA) {
            throw new \LogicException("public key is not a RSA key");
        }
        if ($details['bits'] < 2048) {
            throw new \LogicException("public key must be at least 2048 bits");
        }


        // 2.create IV
        $iv = openssl_random_pseudo_bytes(16);

        // 3. Create ECC keypair on NISTP256
        $g = NISTcurve::generator_256();
        $n = $g->getOrder();

        $secret = Gmp::gmp_random($n);
        $secretG = Point::mul($secret, $g);

        $ecc = new \StdClass();
        $ecc->pubkey = new PublicKey($g, $secretG);
        $ecc->privkey = new PrivateKey($ecc->pubkey, $secret);

        $to_node->setEcc($ecc);
        $to_node->setLineOut($to->line);
        $switchbox->getMesh()->addNode($to_node);


        // 4. SHA256 hash ECC key
        $hash = hash('sha256', hex2bin($ecc->pubkey->encode()), true);

        // 5. Form inner packet
        $header = array(
            'to' => $to->hash,
            'at' => floor(microtime(true) * 1000),
            'line' => $to->line,
        );
        if ($family) {
            $header['family'] = $family;
        }

        $inner_packet = new Packet($switchbox, $header, Utils::convertPemToDer($switchbox->getKeyPair()->getPublicKey()));

        // 6. Encrypt inner packet
        $blob = $inner_packet->encode();
        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv($iv);
        $cipher->setKey($hash);
        $body = $cipher->encrypt($blob);


        // 7. Create sig
        openssl_sign($body, $sig, $switchbox->getKeyPair()->getPrivateKey(), "sha256");

        $ctx = hash_init('sha256');
        hash_update($ctx, hex2bin($ecc->pubkey->encode()));
        hash_update($ctx, hex2bin($to->line));
        $aes_key = hash_final($ctx, true);

        $cipher = new \Crypt_AES(CRYPT_AES_MODE_CTR);
        $cipher->setIv($iv);
        $cipher->setKey($aes_key);
        $aes = $cipher->encrypt($sig);

        $sig = base64_encode($aes);

        // 8. Create open param
        openssl_public_encrypt(hex2bin($ecc->pubkey->encode()), $open, $to->rsaPubKey, OPENSSL_PKCS1_OAEP_PADDING);
        $open = base64_encode($open);

        // 9. Form outer packet
        $header = array(
            'type' => 'open',
            'open' => $open,
            'iv' => bin2hex($iv),
            'sig' => $sig,
        );
        $packet = new Packet($switchbox, $header, $body);

        return $packet;
    }

}
