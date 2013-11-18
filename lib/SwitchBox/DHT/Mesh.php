<?php

namespace SwitchBox\DHT;

use SwitchBox\Packet;
use SwitchBox\Packet\Line;
use SwitchBox\SwitchBox;


// We store nodes twice: one time inside buckets, and one time in a plain hash-list. This makes it easier and quicker
// to find stuff. But we need to do some more work adding/removing nodes though

class Mesh {

    /** @var SwitchBox */
    protected $switchbox;                   // Actual switchbox
    /** @var Bucket[] */
    protected $buckets = array();           // All buckets in the mesh (each for each bit of nodename)
    /** @var Node[] */
    protected $nodes = array();             // List of all nodes, used for quick lookups


    /**
     * @param SwitchBox $switchbox
     */
    public function __construct(SwitchBox $switchbox) {
        $this->switchbox = $switchbox;

        for ($i=0; $i!=256; $i++) {
            $this->buckets[$i] = new Bucket();
        }
    }


    /**
     * Returns all buckets
     *
     * @return \SwitchBox\DHT\Bucket[]
     */
    public function getBuckets()
    {
        return $this->buckets;
    }


    /**
     * Return the switchbox
     *
     * @return \SwitchBox\SwitchBox
     */
    public function getSwitchbox()
    {
        return $this->switchbox;
    }


    /**
     * Find the node that corresponds to this line
     *
     * @param $line
     * @return null|Node
     */
    public function findByLine($line) {
        foreach ($this->getAllnodes() as $node) {
            if ($node->getLineOut() == $line) return $node;
        }
        return null;
    }


    /**
     * Add a node to the mesh
     *
     * @param Node $node
     */
    public function addNode(Node $node) {
        // Add node to correct bucket
        $bucket_id = $this->getSwitchBox()->getSelfNode()->getHash()->getDistanceId($node->getHash());
        $this->buckets[$bucket_id]->addNode($node);

        // Add to array
        $this->nodes[$node->getName()] = $node;
    }


    /**
     * Does this node already exist in our hash?
     *
     * @param $name
     * @return bool
     */
    public function nodeExists($name) {
        return isset($this->nodes[$name]);
    }


    /**
     * Return a list of all nodes that are actually connected
     *
     * @return Node[]
     */
    public function getConnectedNodes() {
        return array_filter($this->nodes, function (Node $e) { return $e->isConnected(); });
    }


    /**
     * Return a list of nodes that are the closest to the given node
     *
     * @param $hash
     * @param int $limit
     * @return Node[]
     */
    public function getClosestForHash($hash, $limit = 3) {
        return array_slice($this->getOrderedNodes($hash), 0, $limit);
    }


    /**
     * Return all nodes
     *
     * @return Node[]
     */
    public function getAllNodes() {
        return $this->nodes;
    }


    /**
     * Return all nodes that starts with nodename
     *
     * @param $partial_name
     * @return array
     */
    public function findMatchingNodes($partial_name) {
        // Find a collection of nodes that STARTS with the name
        $matched_nodes = array_filter($this->nodes, function (Node $e) use ($partial_name) {
            return strpos($e->getName(), $partial_name) === 0;
        });

        return $matched_nodes;
    }


    /**
     * Return node or null if not found
     *
     * @param $name
     * @return null|Node
     */
    public function getNode($name) {
        if ($this->nodeExists($name)) return $this->nodes[$name];
        return null;
    }


    /**
     * Get all nodes sorted by distance
     *
     * @param null $hash
     * @return Node[]
     */
    public function getOrderedNodes($hash = null) {
        $pq = new \SplPriorityQueue();

        if (is_string($hash)) {
            $hash = new Hash($hash);
        }

        foreach ($this->getAllNodes() as $node) {
            $pq->insert($node, $hash->getDistanceId($node->getHash()));
        }

        return array_reverse(iterator_to_array($pq));
    }







}
