<?php
/**
 * Channel class. Encapsulates a reliable data-flow of packets over UDP
 */

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Line\Processor\ChannelProcessor;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\Utils;

class Channel {
    /** @var string */
    protected $id;                      // Hexadecimal channel ID
    /** @var \SwitchBox\DHT\Node */
    protected $to;                      // Destination node
    /** @var array */
    protected $in_queue;                // Queue of incoming packets (still needs assembling)
    /** @var array */
    protected $out_queue;               // Queue of outgoing packets (unacknowledged packets)
    /** @var int */
    protected $last_ack;                // Last acknowledged packet sequence number
    /** @var string */
    protected $type;                    // Line packet type (is this interesting?)
    /** @var bool */
    protected $custom;                  // true when this is a custom type
    /** @var \SwitchBox\SwitchBox */
    protected $switchbox;
    /** @var ChannelProcessor */
    protected $processor;               // Processor to process the packets in this channel
    /** @var int */
    protected $last_activity_ts;        // last time there was activity on this channel
    /** @var bool  */
    protected $end = false;

    const MAX_BACKLOG       = 100;      // Maximum number of unacknowledged packets
    const MAX_RETRIES       = 3;        // Maximum number of retries on a single package


    /**
     * @param SwitchBox $switchbox
     * @param Node $to
     * @param null $id
     */
    public function __construct(SwitchBox $switchbox, Node $to, $id = null) {
        $this->id = $id ? $id : Utils::bin2hex(openssl_random_pseudo_bytes(16), 32);
        print "New Channel ID: ".$this->id."\n";
        $this->to = $to;
        $this->switchbox = $switchbox;

        $this->last_activity_ts = time();

        $this->in_queue = array();
        $this->out_queue = array();
        $this->in_seq = 0;
        $this->out_seq = 0;
        $this->last_ack = 0;

        $this->end = false;

        // Add this new channel to the destination node
        $to->addChannel($this);
    }


    /**
     * @param $type
     * @param ChannelProcessor $processor
     */
    public function addProcessor($type, ChannelProcessor $processor) {
        $this->type = $type;
        $this->custom = (substr($type, 0, 1) == "_");

        $this->processor = $processor;
    }


    /**
     * @param $seq
     * @param Packet $packet
     * @throws \DomainException
     */
    public function addToOutQueue($seq, Packet $packet) {
        if (count($this->out_queue) > self::MAX_BACKLOG) {
            throw new \DomainException("Too many packets in channel ".$this->getId()." backlog");
        }

        if (isset($this->out_queue[$seq])) {
            // If this SEQ is already present, just increase the retry counter
            list($retry, $packet) = $this->out_queue[$seq];
            $this->out_queue[$seq] = array($retry+1, $seq, $packet);
        } else {
            // Add initial SEQ packet
            $this->out_queue[$seq] = array(0, $seq, $packet);
        }
    }


    /**
     * Will acknowledge all packets with seq < $seq by removing them from the out_queue
     *
     * @param $seq
     */
    public function acknowledgePackets($seq) {
        $this->out_queue = array_filter($this->out_queue, function($entry) use ($seq) {
            // Entry = array[retry, seq, packet]
            return $entry[1] < $seq;
        });
//        foreach ($this->out_queue as $k => $v) {
//            if ($k < $seq) unset($this->out_queue[$k]);
//        }
    }


    /**
     * Returns next outgoing sequence number
     *
     * @return int
     */
    public function getNextSequence() {
        return $this->out_seq++;
    }


    /**
     * @return null|string
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @return int
     */
    public function getLastAck() {
        return $this->last_ack;
    }


    /**
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    /**
     * @return \SwitchBox\DHT\Node
     */
    public function getTo()
    {
        return $this->to;
    }


    /**
     * @param $seq
     * @return null
     */
    public function getPacketFromOutQueue($seq) {
        return isset($this->out_queue[$seq]) ? $this->out_queue[$seq] : null;
    }


    /**
     * @return ChannelProcessor
     */
    public function getProcessor() {
        return $this->processor;
    }


    /**
     * Processes a packet that is part of a channel.
     */
    public function process(Packet $packet) {
        $this->last_activity_ts = time();

        print "Processing packet on channel ".$this->getId()."\n";
        $header = $packet->getHeader();

        // remote tells us that we are missing packets. Resend them again by placing them onto the tx-queue
        if (isset($header['miss'])) {
            print "We found missing packets. We need to resend them again...\n";
            foreach ($header['miss'] as $missed_seq) {
                // Fetch packet from out_queue
                $packet = $this->getPacketFromOutQueue($missed_seq);
                if (! $packet) {
                    throw new \DomainException("Cannot locate seq ".$missed_seq." in the out_queue of channel ".$this->getId()."\n");
                }
                // Add this packet to the out_queue (again)
                $this->addToOutQueue($missed_seq, $packet);

                // Queue this packet for transmission
                $this->getSwitchBox()->send($this->getTo(), $packet);
            }
        }

        // We can acknowledge all packets with sequence <= ack by deleting them from the backlog-queue
        if (isset($header['ack'])) {
            $this->acknowledgePackets($header['ack']);
        }

        print "Actual getProcessor()->processIncoming() by ".get_class($this->getProcessor())."!\n";
        $this->getProcessor()->processIncoming($packet);

        // We can end the channel, and delete it and stuff
        if (isset($header['end']) && $header['end']) {
            $this->end = true;
            $this->getTo()->removeChannel($this);
        }
    }


    /**
     * @param Packet $inner_packet
     */
    public function send(Packet $inner_packet) {
        $this->last_activity_ts = time();

        $packet = Line::generate($this->getSwitchBox(), $this->getTo(), $inner_packet);
        $this->getSwitchBox()->send($this->getTo(), $packet);
    }


    /**
     * @param $type
     * @param $extra_headers
     * @param bool $end
     * @return array
     */
    public function createOutChannelHeader($type, $extra_headers, $end = true) {
        $this->last_activity_ts = time();

        $header = array(
            'c' => $this->getId(),
//            'seq' => $this->getNextSequence(),
//            'ack' => $this->getLastAck(),
        );
        if ($type) $header['type'] = $type;
        if ($end) $header['end'] = true;
        return array_merge($header, $extra_headers);
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }


    /**
     * @return bool
     */
    public function isCustom() {
        return $this->custom;
    }


    /**
     * Starts a channel by generating an initial request
     * @param array $args
     */
    public function start(array $args) {
        $this->send($this->getProcessor()->generate($args));
    }


    /**
     * @return int
     */
    public function getIdleTime() {
        return time() - $this->last_activity_ts;
    }


    /**
     * @return string
     */
    public function __toString() {
        return $this->getId()." [Seq: ".$this->out_seq." Acked: ".$this->last_ack." Ended: ".($this->end ? "yes" : "no")."  Idle: ".$this->getIdleTime()."]\n";
    }

}
