<?php

use SwitchBox\Packet\Open;
use SwitchBox\Packet;

include "vendor/autoload.php";

// Initial seeds to connect to
$seeds = array(
//    new SwitchBox\Seed("192.168.56.101", 42424, "33f5af428e94b9d7adb41e4d46be2d2c63471011375dfd4b9c9e3c08616893a5", "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0CSygwvxoMLE+FPgk5Z7\nsLPy7026oDBmGv6S94TR92vtc0iGJ+zCaO+3HnxNDe9HqGA6GPiAsZlx95NqsYMf\nzG7iz5lXXkM6ndIWVzAbLgXwjRHOLtSliK/eiVW1jIwoDzK8whSHwZ2/lIcKZiNs\nnNJFMcdvVqYqfmyi6sEMvKn7YhiMAzGdkE+Z/k0MKgQDSJJekJy5HR1ZUiXny0e8\n6kbBrwwTUqsemz9AGaq4n0STcRn9IgYZLgaLFelGOe2OWveBsScj6FTYziWb4kIY\neSo55aioW9AtSn6gAPQeXnYpc0ZY5gPv6HAwrrkSzua6WHs2hENrCsmU0RWe9iHx\nqwIDAQAB\n-----END PUBLIC KEY-----\n"),
    new SwitchBox\Seed("192.168.56.101", 42424, "d298afbbe12419fca289a03159106d354b9edb8f126e4160e336d466d84edf2f", "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzneSKCRVeNjWn+yQ0J6s\n6Tff1n55i7Zlqf7J+h9HKcQfEDak6ITAedOhEfTpdI5mD2wOcDU5IxrNOv1gAXMB\nzuyaXHd4OkHPBVOUa6R9l6tCJ0ImYcoh/tp1wORnRL+aJTbnrhtLap2iKajHZ29r\nT0EvSIC/pYccDsoA2uInLHVAfm0g5lkiBg4penmodhs/Yx66G117LC4aawFnodGf\nlXIkFwdXZuHZp6+uzqbqeTZQrhSGJ4GolGCTIfCzHAGHcpCgmC5CL7EimNgoINAh\nq0ZMQhQHJpb3ND26LKi7IyUhfhNxPSmlNlKld+Fl2Cidoxtdx61Bo+Ldumyx3CgH\npwIDAQAB\n-----END PUBLIC KEY-----\n"),
//    new SwitchBox\Seed("208.68.164.253", 42424, "5fa6f146d784c9ae6f6d762fbc56761d472f3d097dfba3915c890eec9b79a088", "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxoQkh8uIPe18Ym5kO3VX\nqPhKsc7vhrMMH8HgUO3tSZeIcowHxZe+omFadTvquW4az7CV/+3EBVHWzuX90Vof\nsDsgbPXhzeV/TPOgrwz9B6AgEAq+UZ+cs5BSjZXXQgFrTHzEy9uboio+StBt3nB9\npLi/LlB0YNIoEk83neX++6dN63C3mSa55P8r4FvCWUXue2ZWfT6qamSGQeOPIUBo\n4aiN6P4Hzqaco6YRO9v901jV+nq0qp0yHKnxlIYgiY7501vXWceMtnqcEkgzX4Rr\n7nIoA6QnlUMkTUDP7N3ariNSwl8OL1ZjsFJz7XjfIJMQ+9kd1nNJ3sb4o3jOWCzj\nXwIDAQAB\n-----END PUBLIC KEY-----\n"),
);

// Simple test app
$keypair = new SwitchBox\KeyPair(file_get_contents("privkey.pem"), file_get_contents("pubkey.pem"));
$sb = new SwitchBox\SwitchBox($seeds, $keypair);
$sb->loop();
