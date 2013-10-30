<?php

namespace SwitchBox\DHT;

// Just for the only reason I don't like to use "seeds" as a base-class for sending packets to, and i don't like to use
// "Node" or "Host" to setup initial seeds. So seed and host are interchangeable, but it makes more sense in the code.

// Probably want to refactor this into something decent so we can get rid of all the confusion

class Seed extends Host {

}
