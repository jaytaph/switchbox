<?php

use SwitchBox\DHT\KeyPair;
use SwitchBox\DHT\Mesh;
use SwitchBox\DHT\Node;
use SwitchBox\Packet;

abstract class PacketBase extends PHPUnit_Framework_TestCase {

    protected $their_pub = <<< EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyeFEU0lAVcg6EQ+UTYHK
uBfEH3XKMnGtaxv1yeGks0hBwW5MIrl148mpryPvZe4O0ojBTdNuyOKLMItv3dHa
fm3jWvvt7jJSa8FNV/pwBK1Kd/mGSI/TyJPDOWt/zoPVO+3M1Id9dYMjzFWZdbVA
A5XpPLmtAgTvAYWgn0NQBLjmOfxTgyExvKugejcs6fEzFJCfSTDSBfNvxTjz2PHy
oahCz7V7U7sbGfsy9KDbvBrrcne5SubZGKRwk1IUQfq8quuUzshomluRtNTsFfcZ
t0KLwpnF2pmZYC0PcbpReQ82ZqvSpW35nWO4h1SChwpMk8tBIEaf92LVc29vqBPA
kwIDAQAB
-----END PUBLIC KEY-----
EOD;

    protected $their_priv = <<< EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEAyeFEU0lAVcg6EQ+UTYHKuBfEH3XKMnGtaxv1yeGks0hBwW5M
Irl148mpryPvZe4O0ojBTdNuyOKLMItv3dHafm3jWvvt7jJSa8FNV/pwBK1Kd/mG
SI/TyJPDOWt/zoPVO+3M1Id9dYMjzFWZdbVAA5XpPLmtAgTvAYWgn0NQBLjmOfxT
gyExvKugejcs6fEzFJCfSTDSBfNvxTjz2PHyoahCz7V7U7sbGfsy9KDbvBrrcne5
SubZGKRwk1IUQfq8quuUzshomluRtNTsFfcZt0KLwpnF2pmZYC0PcbpReQ82ZqvS
pW35nWO4h1SChwpMk8tBIEaf92LVc29vqBPAkwIDAQABAoIBAGBs95ysZU1T6OBT
R6vJrdFGkMfSFDUJ9SIu5bR60ZdMkRPaIgWH/hZCMYlVKbjO/0dySJpqvgS3qHIU
d+dEoA85X5oKsTfP02xilRXLqguh1er+RTSNdkPcyCe5//7dG1GXoPl2iedCLywf
SsBdRWkxBomZylgnkR1x/Sl1FlhaOlkE/w7p3lr7QPxfBk/ZyKbePPgb8pWiWaI1
PtwiKcgXQFILd4zrUPtx+R96lrDPvmP8PQ8YTEHwpbsX5FhsPiQNjXFRcpsNQK15
5r9hiq+XJJ0re5+qnP7XNbU2R1H+NbCi14dO/L7ZU/9udT+fF7jNGFgZWyOmLK++
yUs+J7ECgYEA+7LSTkoicby7Es/SI4TjyU62HmGwLJUEIn/M77PqvREuLVi8ha9s
Q1j4jxcJ3lsRE/iIYojgFBXa30mB5uuhM6dER++l9o5SxtqS87RETUFP7TW5JHcC
1IW7LqRwVs3uTfdxIq2OkqeRJDPNNBSGkkFZdaKYAOrj7bZkfIsVrzsCgYEAzVR9
WMNSEyPN0B2wmu8Ki/XV5HQu8wGGTw7Ai3cN+z55ea1XqmRGOU38O2LjM0tQU81t
sM34xh8E4MYRDPUfkntsu440ZalGwqLBlHeUq35dSw6lsTys6wiEzD8vl5isokaM
AE0AthXB8Csshgy+PVH4v8ttBcY11s1cybLvTokCgYB99molYBiIzyjYK0VBHVpZ
/phJ8B2Y0iK1mTvYojPR1u1DKGuAg+AsfDQ4eBEqRGzxj6nBljp+EsnsgP8Pr9CG
5yjWz2pBT8zBU9XDPO4Js6vqTL6RLzYtYZfhqc3Gw1yFjFYEQtNdNZr/gSUq8TK/
sxCwPGTR4Luc1XDIm1qIzQKBgFAzFhqbf+1V6MfHMPnzME1mojrvXn8wM0Oh4XcX
83AVAOGT4U/+hqdPXJvdjhy06BUzggqzCN5ps0AQXQyQmdpfNyMy8ihbK/ZOGApj
gsBRRSNR/0nxByFuXGBitbYivhtMLtbXPNXiPbQPSP6673uIDv7q+BRvTQwvfnSR
3YLxAoGAKK80x8kK4BxfU12rGBmepPJklmPKeCD3qwS+UmczeN0baGkXeqxTapuE
HbcwiQk7yscHqKhG3Tv/Ba6UY/PYkw48sz1V7CZG7vVpIJqqsP4wVZ84yEjW8ZE4
9g3i0D93i2uGerrif1ymxAp9FU9khqny+U3qnLwNFR7poNFirO4=
-----END RSA PRIVATE KEY-----
EOD;


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

    function setUp() {
        // Emulate this side
        $this->my_kp = new KeyPair($this->my_priv, $this->my_pub);
        $this->my_sb = $this->getMockBuilder("\\SwitchBox\\SwitchBox")
                    ->disableOriginalConstructor()
                    ->getMock();
        $this->my_mesh = new Mesh($this->my_sb);

        $this->my_node = new Node(0, 0, $this->my_kp->getPublicKey(), null);
        $this->my_sb->expects($this->any())->method("getKeypair")->will($this->returnValue($this->my_kp));
        $this->my_sb->expects($this->any())->method("getMesh")->will($this->returnValue($this->my_mesh));
        $this->my_sb->expects($this->any())->method("getSelfNode")->will($this->returnValue($this->my_node));


        // Emulate that side
        $this->their_kp = new KeyPair($this->their_priv, $this->their_pub);
        $this->their_sb = $this->getMockBuilder("\\SwitchBox\\SwitchBox")
                    ->disableOriginalConstructor()
                    ->getMock();
        $this->their_mesh = new Mesh($this->their_sb);

        $this->their_node = new Node(0, 0, $this->their_kp->getPublicKey(), null);
        $this->their_sb->expects($this->any())->method("getKeypair")->will($this->returnValue($this->their_kp));
        $this->their_sb->expects($this->any())->method("getMesh")->will($this->returnValue($this->their_mesh));
        $this->their_sb->expects($this->any())->method("getSelfNode")->will($this->returnValue($this->their_node));
    }

}
