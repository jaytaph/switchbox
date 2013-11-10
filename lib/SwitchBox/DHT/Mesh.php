<?php

namespace SwitchBox\DHT;

class Mesh {

    protected $nodes = array();


    function findByLine($line) {
        foreach ($this->nodes as $node) {
            /** @var $node Node */
            //print "Node: ".$node->getName()." Line: ".$node->getLineOut()."\n";
            if ($node->getLineOut() == $line) return $node;
        }
        return null;
    }
    /**
     * @param Node $node
     */
    function addNode(Node $node) {
        print "*** Adding node to mesh: ".$node->getName()."\n";
        $this->nodes[$node->getName()] = $node;
    }

    /**
     * @param $name
     * @return bool
     */
    function nodeExists($name) {
        return isset($this->nodes[$name]);
    }

    function getConnectedNodes() {
        return array_filter($this->nodes, function ($e) { /** @var $e Node */ return $e->isConnected(); });
    }

    function getClosestForHash($hash, $limit = 3) {
        return array_slice($this->getOrderedNodes($hash), 0, $limit);
    }

    function getAllNodes() {
        return $this->nodes;
    }

    function getOrderedNodes($hash = null) {
        $pq = new \SplPriorityQueue();

        if (is_string($hash)) {
            $hash = new Hash($hash);
        }

        foreach ($this->getAllNodes() as $node) {
            $pq->insert($node, $node->getHash()->distance($hash));
        }

        return array_reverse(iterator_to_array($pq));
    }


    /**
     * @param $name
     * @param bool $return_one_only
     * @return null|Node
     */
    function getNode($name, $return_one_only = true) {
        // Looking for a full name. Just find
        if (strlen($name) == 64) {
            if ($this->nodeExists($name)) return $this->nodes[$name];
            return null;
        }

        // Find a collection of nodes that STARTS with the name
        $matched_nodes = array_filter($this->nodes, function ($e) use ($name) {
            /** @var $e Node */
            return strpos($e->getName(), $name) === 0;
        });

        // If only one node matches, we can safely return that node
        if (count($matched_nodes) == 1) return array_shift($matched_nodes);

        // There is more than one node that matches. See if we like to return the whole collection
        return ($return_one_only) ? null : $matched_nodes;
    }

    /**
     * drop hn into its appropriate bucket
     *
     * @param Node $self
     * @param Node $other
     * @param bool $force
     */
    function bucketize(Node $self, Node $other, $force = false) {
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
    function reap() {
        // @TODO
    }

    /**
     * Get any new nodes, and request a line to them
     *
     * @param $hash
     * @return null|Node
     */
    function seen($hash) {
        return $this->getNode($hash);
    }

    // update which lines are elected to keep, rebuild self.buckets array
    function elect() {
        // @TODO
    }

    // every line that needs to be maintained, ping them
    function ping() {
        // @TODO
    }

}
