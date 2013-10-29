<?php

namespace SwitchBox\DHT;

class Mesh {

    protected $nodes = array();


    function findByLine($line) {
        foreach ($this->nodes as $node) {
            /** @var $node Node */
            print "Node: ".$node->getHash()->getHash()." Line: ".$node->getLineOut()."\n";
            if ($node->getLineOut() == $line) return $node;
        }
        return null;
    }
    /**
     * @param Node $node
     */
    function addNode(Node $node) {
        print "*** Adding node to mesh: ".$node->getHash()."\n";
        $this->nodes[$node->getHash()->getHash()] = $node;
    }

    /**
     * @param $hash
     * @return bool
     */
    function nodeExists($hash) {
        return isset($this->nodes[$hash]);
    }

    /**
     * @param $hash
     * @return null|Node
     */
    function getNode($hash) {
        if ($this->nodeExists($hash)) return $this->nodes[$hash];
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

        $bucketNr = $self->getHash()->distance($other->getHash());
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
