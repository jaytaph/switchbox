<?php

include "vendor/autoload.php";

// Initial seeds to connect to.
$seeds = array(
//    // hash-seed (https://github.com/quartzjer/hash-seed) running on localhost.
//    new SwitchBox\DHT\Node("127.0.0.1", 42424, "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzneSKCRVeNjWn+yQ0J6s\n6Tff1n55i7Zlqf7J+h9HKcQfEDak6ITAedOhEfTpdI5mD2wOcDU5IxrNOv1gAXMB\nzuyaXHd4OkHPBVOUa6R9l6tCJ0ImYcoh/tp1wORnRL+aJTbnrhtLap2iKajHZ29r\nT0EvSIC/pYccDsoA2uInLHVAfm0g5lkiBg4penmodhs/Yx66G117LC4aawFnodGf\nlXIkFwdXZuHZp6+uzqbqeTZQrhSGJ4GolGCTIfCzHAGHcpCgmC5CL7EimNgoINAh\nq0ZMQhQHJpb3ND26LKi7IyUhfhNxPSmlNlKld+Fl2Cidoxtdx61Bo+Ldumyx3CgH\npwIDAQAB\n-----END PUBLIC KEY-----\n"),
    //  Telehash.org node
    new SwitchBox\DHT\Node("208.68.164.253", 42424, "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxoQkh8uIPe18Ym5kO3VX\nqPhKsc7vhrMMH8HgUO3tSZeIcowHxZe+omFadTvquW4az7CV/+3EBVHWzuX90Vof\nsDsgbPXhzeV/TPOgrwz9B6AgEAq+UZ+cs5BSjZXXQgFrTHzEy9uboio+StBt3nB9\npLi/LlB0YNIoEk83neX++6dN63C3mSa55P8r4FvCWUXue2ZWfT6qamSGQeOPIUBo\n4aiN6P4Hzqaco6YRO9v901jV+nq0qp0yHKnxlIYgiY7501vXWceMtnqcEkgzX4Rr\n7nIoA6QnlUMkTUDP7N3ariNSwl8OL1ZjsFJz7XjfIJMQ+9kd1nNJ3sb4o3jOWCzj\nXwIDAQAB\n-----END PUBLIC KEY-----\n"),
    // Guybrush experimental node
    new SwitchBox\DHT\Node("85.17.177.236", 42424,  "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA12K/tng567LlEExKr/i+\nbciBnsIADZtlyo00+qUvJXWWidnfVQ7lUixBo84PCoSPd+nQBTsstKjqZ3akV5Nt\nfvqkdnJETeVRZxm/wSShqy3kRktD4qfR+C+BRzvd68+YVNhTe5M6Wp9/mTPZwcNw\ngm3dRA3hrA9FORJZmYi2iLDyvXC9QCPzqLelBPasYgXVzSWXEf7Ss6CdMPioMZ4K\nygGElbulMmCmet1wQ+3/BlxA2IgR5d0pwDztgNl28OsA2Q75+bZqzByH2v3IpNWQ\nRU1Zfd7I6axA4RBn9CKfAf0DpsafP6FmXmf9AKCf5cl5UoUzfy4mw7ipDC/Q30Vl\noQIDAQAB\n-----END PUBLIC KEY-----\n"),
);

// Read or generate keypair
$keypair = new SwitchBox\KeyPair("seed.json", true);
$sb = new SwitchBox\SwitchBox($seeds, $keypair);
print "\n> Online as: [".$sb->getSelfNode()->getName()."]\n\n";

$sb->loop();
exit;
