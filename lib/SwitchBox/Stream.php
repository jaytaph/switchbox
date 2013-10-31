<?php
/**
 * Stream class. Encapsulates a reliable data-flow of packets over UDP
 */

namespace SwitchBox;

use SwitchBox\DHT\Node;
use SwitchBox\Packet\Line;

class Stream {
    /** @var string */
    protected $id;                      // Hexidecimal stream ID
    /** @var \SwitchBox\DHT\Node */
    protected $to;                      // Destination node
    /** @var array */
    protected $in_queue;                // Queue of incoming packets (still needs assembling)
    /** @var array */
    protected $out_queue;               // Queue of outgoing packets (unacknowledged packets)
//    protected $in_seq;
//    protected $out_seq;
//    protected $in_done;
//    protected $out_confirmed;
//    protected $in_dups;
    /** @var int */
    protected $last_ack;
    /** @var string */
    protected $type;                    // Line packet type (is this interesting?)
    /** @var bool */
    protected $custom;                  // true when this is a custom type
    /** @var \SwitchBox\SwitchBox */
    protected $switchbox;
    /** @var iLineProcessor */
    protected $processor;               // Processor to process the packets in this stream

    /** @var bool */
    protected $ended = false;           // Stream has ended

    const MAX_BACKLOG       = 100;      // Maximum number of unacknowledged packets
    const MAX_RETRIES       = 3;        // Maximum number of retries on a single package

    function __construct(SwitchBox $switchbox, Node $to, $type, Line\iLineProcessor $processor, $id = null) {
        $this->id = $id ? $id : Utils::bin2hex(openssl_random_pseudo_bytes(16));
        $this->to = $to;
        $this->switchbox = $switchbox;

        $this->processor = $processor;

        $this->in_queue = array();
        $this->out_queue = array();
        $this->in_seq = 0;
        $this->out_seq = 0;
//        $this->in_done = false;
//        $this->out_confirmed = 0;
//        $this->in_dups = 0;
        $this->last_ack = 0;
        $this->type = $type;
        $this->custom = (substr($type, 0, 1) == "_");

        $this->ended = false;
        $this->backbuffer = array();

        // Add this new stream to the destination node
        $to->addStream($this);
    }

    function addToOutQueue($seq, Packet $packet) {
        if (count($this->out_queue) > self::MAX_BACKLOG) {
            throw new \DomainException("Too many packets in stream ".$this->getId()." backlog");
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
    function acknowledgePackets($seq) {
        $this->out_queue = array_filter($this->out_queue, function($entry) use ($seq) {
            // Entry = array[retry, seq, packet]
            return $entry[1] < $seq;
        });
//        foreach ($this->out_queue as $k => $v) {
//            if ($k < $seq) unset($this->out_queue[$k]);
//        }
    }


//    function endStream($err = null) {
//        $this->ended = true;
//
//        $header = array(
//            'end' => true,
//        );
//        if ($err) {
//            $header['err'] = $err;
//        }
//
//        $packet = new Packet($this->switchbox, $header, null);
//
//        $this->switchbox->tx($this->to, $packet);
//    }


    /**
     * Returns next outgoing sequence number
     *
     * @return int
     */
    function getNextSequence() {
        return $this->out_seq++;
    }

    function getId() {
        return $this->id;
    }

    function getLastAck() {
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

    function getPacketFromOutQueue($seq) {
        return isset($this->out_queue[$seq]) ? $this->out_queue[$seq] : null;
    }


    function getProcessor() {
        return $this->processor;
    }

    /**
     * Processes a packet that is part of a stream.
     */
    public function process(Packet $packet) {
        $header = $packet->getHeader();

        // remote tells us that we are missing packets. Resend them again by placing them onto the tx-queue
        if (isset($header['miss'])) {
            print "We found missing packets. We need to resend them again...\n";
            foreach ($header['miss'] as $missed_seq) {
                // Fetch packet from out_queue
                $packet = $this->getPacketFromOutQueue($missed_seq);
                if (! $packet) {
                    throw new \DomainException("Cannot locate seq ".$missed_seq." in the out_queue of stream ".$this->getId()."\n");
                }
                // Add this packet to the out_queue (again)
                $this->addToOutQueue($missed_seq, $packet);

                // Queue this packet for transmission
                $this->getSwitchBox()->tx($this->getTo(), $packet);
            }
        }

        // We can acknowledge all packets with sequence <= ack by deleting them from the backlog-queue
        if (isset($header['ack'])) {
            $this->acknowledgePackets($header['ack']);
        }

        // We can end the stream, and delete it and stuff
        if (isset($header['end']) && $header['end']) {
            $this->ended = true;
        }

        $this->getProcessor()->process($this->getSwitchBox(), $this->getTo(), $packet);
    }

    function send(Packet $inner_packet) {
        print "\n\n\n\nSending stream packet.....\n\n\n";
        $packet = Line::generate($this->getSwitchBox(), $this->getTo(), $inner_packet);
        $this->getSwitchBox()->getTxQueue()->enqueue_packet($this->getTo(), $packet);
    }

}