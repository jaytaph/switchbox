<?php

namespace SwitchBox\DHT;

class Mesh {

    protected $nodes = array();


    public function findByLine($line) {
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

    public function getConnectedNodes() {
        return array_filter($this->nodes, function ($e) { /** @var $e Node */ return $e->isConnected(); });
    }

    public function getClosestForHash($hash, $limit = 3) {
        return array_slice($this->getOrderedNodes($hash), 0, $limit);
    }

    public function getAllNodes() {
        return $this->nodes;
    }

    public function findMatchingNodes($partial_name) {
        // Find a collection of nodes that STARTS with the name
        $matched_nodes = array_filter($this->nodes, function ($e) use ($partial_name) {
            /** @var $e Node */
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


    public function getOrderedNodes($hash = null) {
        $pq = new \SplPriorityQueue();

        if (is_string($hash)) {
            $hash = new Hash($hash);
        }

        foreach ($this->getAllNodes() as $node) {
            $pq->insert($node, $node->getHash()->distance($hash));
        }

        return array_reverse(iterator_to_array($pq));
    }

}
