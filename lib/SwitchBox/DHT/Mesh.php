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

    function addHost(Host $host) {
        print "*** Adding host to mesh: ".$host->getName()."\n";
        $this->nodes[$host->getName()] = $host;
    }

    /**
     * @param $name
     * @return bool
     */
    function nodeExists($name) {
        return isset($this->nodes[$name]);
    }

    function getConnectedNodes() {
        return array_filter($this->nodes, function ($e) { return $e->isConnected(); });
        //return $this->nodes;
    }

    function getAllNodes() {
        return $this->nodes;
    }


    /**
     * @param $name
     * @return null|Node
     */
    function getNode($name) {
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
    function bucketize(Node $self, Node $other, $force = false) {
        if (! $force && ! $other->getBucket()) return;

        $hash_self = new Hash($self->getName());
        $hash_other = new Hash($other->getName());
        $bucketNr = $hash_self->distance($hash_other);
        $self->addToBucket($bucketNr, $other);

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
