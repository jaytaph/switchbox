<?php

// This is the processor interface that allows us to generate and respond to different line types

namespace SwitchBox\Packet\Line;

use SwitchBox\DHT\Node;
use SwitchBox\Packet;
use SwitchBox\SwitchBox;
use SwitchBox\Stream;

//   inRequest:
//      somebody requests to do something on a line (like: i want to peer to 'hash')
//   outResponse:
//      the response we send to something to did an inRequest
//   inResponse:
//      we requested a peer request, and this is the incoming result packet
//   outRequest:
//      This is the request we make to o something, like finding a peer


interface iLineProcessor {

    static function inResponse(SwitchBox $switchbox, Node $node, Packet $packet);
    static function inRequest(SwitchBox $switchbox, Node $node, Packet $packet);

    static function outResponse(Stream $stream, array $args);
    static function outRequest(Stream $stream, array $args);

}
