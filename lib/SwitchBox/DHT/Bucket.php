<?php

namespace SwitchBox\DHT;

class Bucket implements \IteratorAggregate, \Countable {
    const K = 8;                    // Maximum number of nodes that a bucket can contain

    /** @var \SplObjectStorage */
    protected $nodes;
    /** @var int */
    protected $evictions;           // Number of times a node has been replaced with a better node

    /**
     *
     */
    public function __construct() {
        $this->nodes = new \SplObjectStorage();

        $this->evictions = 0;
    }


    /**
     * Add a node to this bucket. Evict bad nodes if needed.
     *
     * @param Node $node
     */
    public function addNode(Node $node) {
        // Just add when our bucket isn't full yet. Attach deals with duplicate nodes
        if (! $this->isFull()) {
            $this->nodes->attach($node);
            return;
        }

        // Bucket is full. We have to decide if and how we want to replace a node
        // First, we need to check which nodes are good
        $nodes = array();
        foreach ($this->nodes as $node) {
            /** @var $node Node */
            $health = $node->getHealth();

            if (! isset($nodes[$health])) $nodes[$health] = array();
            $nodes[$health][] = $node;
        }

        // Sorry, no bad nodes, so we can't
        if (! isset($nodes[Node::HEALTH_BAD])) return;

        // Remove all bad nodes
        foreach ($nodes[Node::HEALTH_BAD] as $node) {
            $this->evictions++;
            $this->nodes->detach($node);
        }

        if (! $this->isFull()) {
            $this->nodes->attach($node);
        }
    }


    /**
     * Returns true when this bucket is full
     *
     * @return bool
     */
    public function isFull() {
        return count($this->nodes) >= self::K;
    }


    /**
     * Return the number of bad nodes that have been replaced by other nodes
     */
    public function getEvictions() {
        return $this->evictions;
    }


    /**
     * Returns bucket iterator
     *
     * @return \SplObjectStorage|\Traversable
     */
    public function getIterator()
    {
        return $this->nodes;
    }


    /**
     * Returns number of nodes in bucket, useful for using with phps's count()
     *
     * @return int
     */
    public function count()
    {
        return count($this->getIterator());
    }

}
