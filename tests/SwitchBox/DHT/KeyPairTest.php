<?php

use SwitchBox\DHT\KeyPair;

class SwitchBox_DHT_KeyPairTest extends PHPUnit_Framework_TestCase {

    protected $pub = <<< EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsg5qSMpD3Opyz4w0EGe3
hVSR9gAjrg7LOYsImTWp9ZrT56HTFLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYu
yRyfoNbSbHvF2bvMroH4K1e1k/C0fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY
787rqSEuiXgLk1q+9w3XChCvi/HMbIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28
aSxql1OhWxzRCgTrLu52qx5jxBO6lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAP
CfHWQG8qzx+ZYZcupvYjo3xL9RWDlYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXO
jQIDAQAB
-----END PUBLIC KEY-----
EOD;

    protected $priv =  <<< EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAsg5qSMpD3Opyz4w0EGe3hVSR9gAjrg7LOYsImTWp9ZrT56HT
FLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYuyRyfoNbSbHvF2bvMroH4K1e1k/C0
fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY787rqSEuiXgLk1q+9w3XChCvi/HM
bIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28aSxql1OhWxzRCgTrLu52qx5jxBO6
lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAPCfHWQG8qzx+ZYZcupvYjo3xL9RWD
lYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXOjQIDAQABAoIBAF4xvgxP5GLELISb
mRdspuuD98t5+2YjMWN5p8zVVNo3VA7fzvjNoz+PUrj+HKuqnd1YY2nAtb68Hu6p
x4uaRNnjqJa/zoXRXLN45CkVXKIhPk/LMM/y498zqd6hnTWNj9w7nDQH5CaGIrlZ
gUtLo0eJ4aWRhjr3JLiJcyjjS3VoyCITJakVNvzLdkBMz5IqeJv13Mrgushxv1GM
c5aqCrLtvCjLY3M9KwvtBnACXk+qAe12vg/UcV1g+xjUFbQar7IG1xyUi5Vsjgjb
8OXnalnnTBNQW9kLoZvLF2ek8+DizD+gpK9I/Pac56FYbX7Jx3nODjmnTBvUXSBd
ihNV84kCgYEA4PCqrDpsI8H8FG6huzg8LhG9bi9BM5eaIGMi36z+sKBfZvXZ2zv3
3N/Da3xGyP/rXJXHivG4NSWR3gFPfmoMpUqMF7CVevQRJY/OOVZ3F0DAHEqOnEaM
XeT7yRhKQWOdpzYNWUd6oqERXC2N49gpqyjf28tm6sfsueCBxOamUC8CgYEAyqR3
/pFYFHe/cVqJbJ20F6RqxrEdatxwsq9Fnu3fnz+iW1Fv/u3FX9gHNSRvXcoXWy8e
oYN9Bwpt7C628+FTkVAnGQldoJgGLAyzm6yI7WEv6P3N5X2i0oV6MJgxqz2XKLGH
DEhSB6hD32ZknfRegr61YvgGKJX0kniqFIGVggMCgYEAycMVU6aTmP9GvIz/RI8M
a8Y9w7dfJIe3F5XUkgz55jPzXsbmwl7n1JZhEuhGFcR3uHQgp+Bo+kLYs+k5BIrb
DOfxAM7DRaXmO2rh70w/RfwuVTIK+OHOxem+boH7GOvhXTp+frY+qeEPUT8LJnOd
7IidQukPR0hMbe2SeKrqQsECgYAXNj20gEuZlJnuTxOcyHe/mYrNla4r9nJGVYNh
EBhkcnKTiUGN7wiD0QgKU1EaajLAtCYLFDe3Hb+3pSY5y166L3c7C/KYmbFjTFUq
iNnqbw6A3sm99uU2vilf9Z8C4Xw2Ihe5FXOoAuM7bMwrt7k3usamPojeD0dDm+TH
koxgpwKBgC91YqZ6VVK7QGBaHOTSERduxdX310fD74e67chqIeDWJcRn7cSP3XCR
1RuNrAiVlEAG+v+BfSIRa7WcR4X5bH56fYmhdy4wVwgB4urloVlr0C+iZMb2RyD4
drdBEDisrKExBsTbW4NZHD2CDaka5OfHi/nQBibNKVgmiS6aCE6b
-----END RSA PRIVATE KEY-----
EOD;


    function testConstruct() {
        $kp = new Keypair($this->priv, $this->pub);
        $this->assertEquals($kp->getPrivateKey(), $this->priv);
        $this->assertEquals($kp->getPublicKey(), $this->pub);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGenerateFromFileNotFound() {
        $tmpname = tempnam("/tmp", "phpunit");
        unlink($tmpname);
        KeyPair::fromFile($tmpname, false);
    }

    function testGenerateFromFile() {
        $tmpname = tempnam("/tmp", "phpunit");
        unlink($tmpname);

        $kp = KeyPair::fromFile($tmpname, true);
        $this->assertStringStartsWith("-----BEGIN PUBLIC KEY-----", $kp->getPublicKey());
        $this->assertStringStartsWith("-----BEGIN PRIVATE KEY-----", $kp->getPrivateKey());
    }

    function testGenerateFromFile2() {
        $tmpname = tempnam("/tmp", "phpunit");
        unlink($tmpname);

        $kp1 = KeyPair::fromFile($tmpname, true);
        $kp2 = KeyPair::fromFile($tmpname, false);
        $this->assertEquals($kp1->getPublicKey(), $kp2->getPublicKey());
    }


    function testBits() {
        $kp = KeyPair::generate(512);
        $res = openssl_pkey_get_public($kp->getPublicKey());
        $details = openssl_pkey_get_details($res);
        $this->assertEquals($details['bits'], 512);

        $kp = KeyPair::generate(2048);
        $res = openssl_pkey_get_public($kp->getPublicKey());
        $details = openssl_pkey_get_details($res);
        $this->assertEquals($details['bits'], 2048);
    }

    function testDER() {
        $kp = new Keypair($this->priv, $this->pub);
        $this->assertEquals($kp->getPublicKey(KeyPair::FORMAT_PEM), $this->pub);

        // base64 encoding of the DER formatted key
        $this->assertEquals(base64_encode($kp->getPublicKey(KeyPair::FORMAT_DER)), "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsg5qSMpD3Opyz4w0EGe3hVSR9gAjrg7LOYsImTWp9ZrT56HTFLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYuyRyfoNbSbHvF2bvMroH4K1e1k/C0fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY787rqSEuiXgLk1q+9w3XChCvi/HMbIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28aSxql1OhWxzRCgTrLu52qx5jxBO6lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAPCfHWQG8qzx+ZYZcupvYjo3xL9RWDlYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXOjQIDAQAB");
    }

    function testConvertDERtoPEM() {
        $der1 = base64_decode("MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsg5qSMpD3Opyz4w0EGe3hVSR9gAjrg7LOYsImTWp9ZrT56HTFLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYuyRyfoNbSbHvF2bvMroH4K1e1k/C0fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY787rqSEuiXgLk1q+9w3XChCvi/HMbIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28aSxql1OhWxzRCgTrLu52qx5jxBO6lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAPCfHWQG8qzx+ZYZcupvYjo3xL9RWDlYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXOjQIDAQAB");

        $der2 = KeyPair::convertPemToDer($this->pub);
        $this->assertEquals($der1, $der2);
    }

    /**
     * @expectedException \runtimeException
     */
    function testConvertDERtoPEMWithException() {
        KeyPair::convertPemToDer("This is a bad PEM formatted key");
    }

    function testConvertPEMtoDER() {
        $der = base64_decode("MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsg5qSMpD3Opyz4w0EGe3hVSR9gAjrg7LOYsImTWp9ZrT56HTFLG3Wekgcnx5sywujEBzy6JeTZqWRKzyhvYuyRyfoNbSbHvF2bvMroH4K1e1k/C0fF9PZHZEvw/nXHPCsoJnKk97UHUHg1Ty/tcY787rqSEuiXgLk1q+9w3XChCvi/HMbIkLqAWXROaw6vBOvOUIiL+n3npR2S5kQK28aSxql1OhWxzRCgTrLu52qx5jxBO6lmUbPTvTD8fwMQDe7t2cpS7+BrHJPbZfyKAPCfHWQG8qzx+ZYZcupvYjo3xL9RWDlYqvN0kjwmCyJJoQqUn1hxTOg0LJoQlPgwXOjQIDAQAB");

        $pem = KeyPair::convertDerToPem($der);
        $this->assertEquals($der, KeyPair::convertPemToDer($pem));
    }

}
