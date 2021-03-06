<?php

namespace SwitchBox\Packet\Line\Processor;

use SwitchBox\Packet;
use SwitchBox\Packet\Line\Channel;

class Peer extends ChannelProcessor {

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
        $channel = new Channel($this->getSwitchbox(), $node);
        $channel->addProcessor("connect", new Connect($channel));
        $channel->start(array(
            'ip' => $this->getNode()->getIp(),
            'port' => $this->getNode()->getPort(),
            'pub_key' => $this->getNode()->getPublicKey(),
        ));

//        $header = $this->getChannel()->createOutChannelHeader('', array(), true);
//        $this->getChannel()->send(new Packet($header, null));
    }


    public function generate(array $args)
    {
        print "*** generate PEER\n";
        $hash = $args['hash'];

        $header = $this->getChannel()->createOutChannelHeader('peer', array('peer' => $hash), true);
        return new Packet($header, null);
    }

}
