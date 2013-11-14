<?php

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Peer extends streamProcessor {


    public function processIncoming(Packet $packet)
    {
        $header = $packet->getHeader();
        print ANSI_MAGENTA;
        print_r($header);
        print ANSI_RESET;

        if (isset($header['peer'])) {
            $this->_peer($header['peer']);
        }

    }

    protected function _peer($peer) {
        $node = $this->getSwitchbox()->getMesh()->getNode($peer);
        if (! $node) {
            print ANSI_RED . "Cannot find the peer to connect to..." . ANSI_RESET . "\n";
            return null;
        }

        // Make a connection request to the other side
        $stream = new Stream($this->getSwitchbox(), $node);
        $stream->addProcessor("connect", new Connect($stream));
        $stream->start(array(
            'ip' => $this->getNode()->getIp(),
            'port' => $this->getNode()->getPort(),
            'pub_key' => $this->getNode()->getPublicKey(),
        ));

//        $header = $this->getStream()->createOutStreamHeader('', array(), true);
//        $this->getStream()->send(new Packet($this->getSwitchBox(), $header, null));
    }


    public function generate(array $args)
    {
        print "*** generate PEER\n";
        $hash = $args['hash'];

        $header = $this->getStream()->createOutStreamHeader('peer', array('peer' => $hash), false);
        return new Packet($this->getSwitchBox(), $header, null);
    }

}
