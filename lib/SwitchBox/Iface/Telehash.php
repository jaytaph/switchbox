<?php

namespace SwitchBox\Iface;

use SwitchBox\DHT\Node;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\PacketHandler;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\TxQueue;

class Telehash extends Sock {

    /** @var SwitchBox */
    protected $switchbox;

    /** @var TxQueue */
    protected $txqueue;

    /** @var StreamProcessor[]  */
    protected $packet_handlers = array();


    function __construct(Switchbox $switchbox, $udp_port) {
        $this->switchbox = $switchbox;

        $this->txqueue = new TxQueue();

        // Setup UDP mesh socket
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->sock);
        socket_bind($this->sock, 0, $udp_port);

        // Add packet handlers
        $this->addPacketHandler(Packet::TYPE_PING, new Ping($switchbox));
        $this->addPacketHandler(Packet::TYPE_OPEN, new Open($switchbox));
        $this->addPacketHandler(Packet::TYPE_LINE, new Line($switchbox));
    }


    public function handle(SwitchBox $switchbox, $sock)
    {
        if ($sock == $this->sock) {
            $this->_handleSocket($switchbox, $sock);
        }
    }

    /**
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    protected function _handleSocket($sock) {
        $ip = "";
        $port = 0;
        socket_recvfrom($sock, $buf, 2048, 0, $ip, $port);
        print "loop() Connection from: $ip : $port (".strlen($buf)."/".strlen(bin2hex($buf))." bytes)\n";

        // Check if we received something from ourselves. If so, skip
        if ($ip == $this->getSwitchBox()->getSelfNode()->getIp() && $port == $this->getSwitchBox()->getSelfNode()->getPort()) {
            print "Loop() received data from self. Skipping!\n";
            return;
        }

        // Decode the packet
        $packet = Packet::decode($this->getSwitchBox(), $buf, $ip, $port);
        if ($packet == NULL) {
            print "loop() Unknown data. Not a packet!\n";
            return;
        }

        print "loop() Incoming '".ANSI_WHITE . $packet->getType(true). ANSI_RESET."' packet from ".$packet->getFromIp().":".$packet->getFromPort()."\n";

        if (isset($this->packet_handlers[$packet->getType()])) {
            $this->packet_handlers[$packet->getType()]($this->getSwitchBox(), $packet);
        } else {
            print ANSI_RED;
            print ("loop() Cannot decode this type of packet yet :(\n");
            print_r($packet->getHeader());
            print ANSI_RESET;
        }
    }


    /**
     * Add a packet handler
     * @param $type
     * @param callable $cb
     */
    protected function addPacketHandler($type, PacketHandler $cb) {
        $this->packet_handlers[$type] = $cb;
    }

    function getPacketHandler($type) {
        if (isset($this->packet_handlers[$type])) {
            return $this->packet_handlers[$type];
        }
        return null;
    }



    public function addStreamProcessor($type, callable $cb) {
        // Find the line processor
        if (! isset($this->packet_handlers[Packet::TYPE_LINE])) {
            throw new \OutOfRangeException("Cannot find the LINE type packet handler");
        }
        $this->packet_handlers[Packet::TYPE_LINE]->addStreamProcessor($type, $cb);
    }


    public function send($packet, $ip, $port) {
        print "Sending packet to ".$ip.":".$port."\n";
        return socket_sendto($this->sock, $packet, strlen($packet), 0, $ip, $port);
    }


    public function flush() {
        // Process any items that are inside the transmission queue
        if (! $this->txqueue->isEmpty()) {
            print count($this->txqueue)." packet(s) queued.\n";

            while (!$this->txqueue->isEmpty()) {
                $item = $this->txqueue->dequeue();

                $bin_packet = $item['packet'];
                /** @var $bin_packet Packet */
                $this->send($bin_packet->encode(), $item['ip'], $item['port']);
            }
        }
    }

    public function enqueue(Node $node, Packet $packet) {
        $this->txqueue->enqueue_packet($node, Open::generate($this->getSwitchBox(), $node, null));
    }

    public function getSelectSockets()
    {
        return array($this->sock);
    }

}
