<?php


use SwitchBox\DHT\KeyPair;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;

class SwitchBox_DHT_MeshTest extends PHPUnit_Framework_TestCase {

    protected $my_pub = <<< EOD
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

    protected $my_priv =  <<< EOD
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

    /** @var Mesh */
    protected $mesh;

    function setUp() {
        $my_sb = $this->getMockBuilder("\\SwitchBox\\SwitchBox")
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->mesh = new Mesh($my_sb);

        $my_kp = new KeyPair($this->my_priv, $this->my_pub);
        $my_node = new Node(0, 0, $my_kp->getPublicKey(), null);
        $my_sb->expects($this->any())->method("getKeypair")->will($this->returnValue($my_kp));
        $my_sb->expects($this->any())->method("getMesh")->will($this->returnValue($this->mesh));
        $my_sb->expects($this->any())->method("getSelfNode")->will($this->returnValue($my_node));

    }

    function testConstruction() {
        $this->assertCount(256, $this->mesh->getBuckets());
    }

    function testgetNode() {
        $this->assertNull($this->mesh->getNode(hash("sha256", "foobar")));

        $node = new Node("127.0.0.1", 12345, null, hash("sha256", "foobar"));
        $this->mesh->addNode($node);
        $this->assertNotNull($this->mesh->getNode(hash("sha256", "foobar")));
    }

    function testgetAllNodes() {
        $this->assertCount(0, $this->mesh->getAllNodes());

        $node = new Node("127.0.0.1", 12345, null, hash("sha256", "foobar"));
        $this->mesh->addNode($node);
        $this->assertCount(1, $this->mesh->getAllNodes());

        $node = new Node("127.0.0.1", 12345, null, hash("sha256", "foobar2"));
        $this->mesh->addNode($node);
        $this->assertCount(2, $this->mesh->getAllNodes());

        $node = new Node("127.0.0.1", 12345, null, hash("sha256", "foobar3"));
        $this->mesh->addNode($node);
        $this->assertCount(3    , $this->mesh->getAllNodes());
    }


}
