<?php

namespace SwitchBox\DHT;

use SwitchBox\iSockHandler;
use SwitchBox\Packet;
use SwitchBox\Packet\Line;
use SwitchBox\Packet\Open;
use SwitchBox\Stream;
use SwitchBox\SwitchBox;

class Mesh implements iSockHandler {

    protected $nodes = array();

    /** @var resource UDP socket connecting to mesh */
    protected $sock;


    function __construct($udp_port = 42424) {
        // Setup UDP mesh socket
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->sock);
        socket_bind($this->sock, 0, $udp_port);
    }


    public function findByLine($line) {
        foreach ($this->getAllnodes() as $node) {
            //print "Node: ".$node->getName()." Line: ".$node->getLineOut()."\n";
            if ($node->getLineOut() == $line) return $node;
        }
        return null;
    }
    /**
     * @param Node $node
     */
    public function addNode(Node $node) {
        print "*** Adding node to mesh: ".$node->getName()."\n";
        $this->nodes[$node->getName()] = $node;
    }

    /**
     * @param $name
     * @return bool
     */
    public function nodeExists($name) {
        return isset($this->nodes[$name]);
    }

    /**
     * @return Node[]
     */
    public function getConnectedNodes() {
        return array_filter($this->nodes, function (Node $e) { return $e->isConnected(); });
    }

    public function getClosestForHash($hash, $limit = 3) {
        return array_slice($this->getOrderedNodes($hash), 0, $limit);
    }

    /**
     * @return Node[]
     */
    public function getAllNodes() {
        return $this->nodes;
    }

    public function findMatchingNodes($partial_name) {
        // Find a collection of nodes that STARTS with the name
        $matched_nodes = array_filter($this->nodes, function (Node $e) use ($partial_name) {
            return strpos($e->getName(), $partial_name) === 0;
        });

        return $matched_nodes;
    }

    /**
     * @param $name
     * @param bool $return_one_only
     * @return null|Node
     */
    public function getNode($name, $return_one_only = true) {
        if ($this->nodeExists($name)) return $this->nodes[$name];
        return null;
    }

    /**
     * drop hn into its appropriate bucket
     *
     * @param Node $self
     * @param Node $other
     * @param bool $force
     */
    public function bucketize(Node $self, Node $other, $force = false) {
    //        if (! $force && ! $other->getBucket()) return;
    //
    //        $hash_self = new Hash($self->getName());
    //        $hash_other = new Hash($other->getName());
    //        $bucketNr = $hash_self->distance($hash_other);
    //        $self->addToBucket($bucketNr, $other);
    }

    /**
     * delete any dead hashnames
     */
    public function reap() {
        // @TODO
    }

    /**
     * Get any new nodes, and request a line to them
     *
     * @param $hash
     * @return null|Node
     */
    public function seen($hash) {
        return $this->getNode($hash);
    }

    // update which lines are elected to keep, rebuild self.buckets array
    public function elect() {
        // @TODO
    }

    // every line that needs to be maintained, ping them
    public function ping() {
        // @TODO
    }


    /**
     * @param null $hash
     * @return Node[]
     */
    public function getOrderedNodes($hash = null) {
        $pq = new \SplPriorityQueue();

        if (is_string($hash)) {
            $hash = new Hash($hash);
        }

        foreach ($this->getAllNodes() as $node) {
            $pq->insert($node, $hash->distance($node->getHash()));
        }

        return array_reverse(iterator_to_array($pq));
    }


    public function send($packet, $ip, $port) {
        print "Sending packet to ".$ip.":".$port."\n";
        socket_sendto($this->sock, $packet, strlen($packet), 0, $ip, $port);
    }

    public function getSelectSockets()
    {
        return array($this->sock);
    }

    public function handle(SwitchBox $switchbox, $sock)
    {
        if ($sock == $this->sock) {
            $this->_handleSocket($switchbox, $sock);
        }
    }

    protected function _handleSocket(SwitchBox $switchbox, $sock) {
        $ip = "";
        $port = 0;
        socket_recvfrom($sock, $buf, 2048, 0, $ip, $port);
        print "loop() Connection from: $ip : $port (".strlen($buf)."/".strlen(bin2hex($buf))." bytes)\n";

        // Check if we received something from ourselves. If so, skip
        if ($ip == $switchbox->getSelfNode()->getIp() && $port == $switchbox->getSelfNode()->getPort()) {
            print "Loop() received data from self. Skipping!\n";
            return;
        }

        // Decode the packet
        $packet = Packet::decode($switchbox, $buf, $ip, $port);
        if ($packet == NULL) {
            print "loop() Unknown data. Not a packet!\n";
            return;
        }

        print "loop() Incoming '".ANSI_WHITE . $packet->getType(true). ANSI_RESET."' packet from ".$packet->getFromIp().":".$packet->getFromPort()."\n";

        // @TODO: Packet should already have its processor:
        // $packet->process($switchbox);

        if ($packet->getType() == Packet::TYPE_PING) {
            // Do nothing..
            return;
        }

        if ($packet->getType() == Packet::TYPE_OPEN) {
            $node = Open::process($switchbox, $packet);

            if ($node->isConnected()) {
                print ANSI_GREEN."Finalized connection with ".(string)$node."!!!!!".ANSI_RESET."\n";
                print_r($node->getInfo());

                // Try and do a seek to ourselves, this allows us to find our outside IP/PORT
                $stream = new Stream($switchbox, $node);
                $stream->addProcessor("seek", new Line\Seek($stream));
                $stream->start(array(
                    'hash' => $switchbox->getSelfNode()->getName(),
                ));
            } else {
                print ANSI_YELLOW."Node ".(string)$node." is not yet connected. ".ANSI_RESET."\n";
                $switchbox->getTxQueue()->enqueue_packet($node, Open::generate($switchbox, $node, null));
            }

            return;
        }
        if ($packet->getType() == Packet::TYPE_LINE) {
            Line::process($switchbox, $packet);
            return;
        }

        printf ("loop() Cannot decode this type of packet yet :(\n");
    }

}
