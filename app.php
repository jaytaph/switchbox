<?php

include "vendor/autoload.php";

// Initial seeds to connect to.
$seeds = array(
    // hash-seed (https://github.com/quartzjer/hash-seed) running on localhost.
    new SwitchBox\DHT\Seed("127.0.0.1", 42424, "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzneSKCRVeNjWn+yQ0J6s\n6Tff1n55i7Zlqf7J+h9HKcQfEDak6ITAedOhEfTpdI5mD2wOcDU5IxrNOv1gAXMB\nzuyaXHd4OkHPBVOUa6R9l6tCJ0ImYcoh/tp1wORnRL+aJTbnrhtLap2iKajHZ29r\nT0EvSIC/pYccDsoA2uInLHVAfm0g5lkiBg4penmodhs/Yx66G117LC4aawFnodGf\nlXIkFwdXZuHZp6+uzqbqeTZQrhSGJ4GolGCTIfCzHAGHcpCgmC5CL7EimNgoINAh\nq0ZMQhQHJpb3ND26LKi7IyUhfhNxPSmlNlKld+Fl2Cidoxtdx61Bo+Ldumyx3CgH\npwIDAQAB\n-----END PUBLIC KEY-----\n"),
    // Telehash.org node
    new SwitchBox\DHT\Seed("208.68.164.253", 42424, "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxoQkh8uIPe18Ym5kO3VX\nqPhKsc7vhrMMH8HgUO3tSZeIcowHxZe+omFadTvquW4az7CV/+3EBVHWzuX90Vof\nsDsgbPXhzeV/TPOgrwz9B6AgEAq+UZ+cs5BSjZXXQgFrTHzEy9uboio+StBt3nB9\npLi/LlB0YNIoEk83neX++6dN63C3mSa55P8r4FvCWUXue2ZWfT6qamSGQeOPIUBo\n4aiN6P4Hzqaco6YRO9v901jV+nq0qp0yHKnxlIYgiY7501vXWceMtnqcEkgzX4Rr\n7nIoA6QnlUMkTUDP7N3ariNSwl8OL1ZjsFJz7XjfIJMQ+9kd1nNJ3sb4o3jOWCzj\nXwIDAQAB\n-----END PUBLIC KEY-----\n"),
);

// Read or generate keypair
$keypair = new SwitchBox\KeyPair("seed.json", true);
$sb = new SwitchBox\SwitchBox($seeds, $keypair);
print "\n*** Online as: [".$sb->getSelfNode()->getName()."]\n\n";

$sb->loop();
exit;
