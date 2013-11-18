<?php

namespace SwitchBox\Iface;

use SwitchBox\Packet\Line;
use SwitchBox\Packet\Line\Processor\StreamProcessor;
use SwitchBox\Packet\Open;
use SwitchBox\Packet\PacketHandler;
use SwitchBox\Packet\Ping;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;

class Telehash extends SockHandler {

    /** @var SwitchBox */
    protected $switchbox;

    /** @var StreamProcessor[]  */
    protected $packet_handlers = array();           // Handlers that deal with the different packets


    /**
     * @param SwitchBox $switchbox
     * @param $udp_port
     */
    public function __construct(Switchbox $switchbox, $udp_port) {
        $this->switchbox = $switchbox;

        // Setup UDP mesh socket
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->sock);
        socket_bind($this->sock, 0, $udp_port);

        // Add packet handlers
        $this->addPacketHandler(Packet::TYPE_PING, new Ping($switchbox));
        $this->addPacketHandler(Packet::TYPE_OPEN, new Open($switchbox));
        $this->addPacketHandler(Packet::TYPE_LINE, new Line($switchbox));
    }


    /**
     * @param SwitchBox $switchbox
     * @param $sock
     */
    public function handle(SwitchBox $switchbox, $sock)
    {
        if ($sock == $this->sock) {
            $this->_handleSocket($sock);
        }
    }


    /**
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    /**
     * @param $sock
     */
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
            $this->packet_handlers[$packet->getType()]->process($packet);
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
     * @param \SwitchBox\Packet\PacketHandler $handler
     */
    protected function addPacketHandler($type, PacketHandler $handler) {
        $this->packet_handlers[$type] = $handler;
    }


    /**
     * @param $type
     * @return StreamProcessor
     */
    public function getPacketHandler($type) {
        if (isset($this->packet_handlers[$type])) {
            return $this->packet_handlers[$type];
        }
        return null;
    }


    /**
     * @param $type
     * @param callable $cb
     * @throws \OutOfRangeException
     */
    public function addStreamProcessor($type, callable $cb) {
        // Find the line processor
        if (! isset($this->packet_handlers[Packet::TYPE_LINE])) {
            throw new \OutOfRangeException("Cannot find the LINE type packet handler");
        }
        $this->packet_handlers[Packet::TYPE_LINE]->addStreamProcessor($type, $cb);
    }


    /**
     * @param $packet
     * @param $ip
     * @param $port
     * @return int
     */
    public function send($packet, $ip, $port) {
        print "Sending packet to ".$ip.":".$port."\n";
        return socket_sendto($this->sock, $packet, strlen($packet), 0, $ip, $port);
    }


    /**
     * @return array
     */
    public function getSelectSockets()
    {
        return array($this->sock);
    }

}
