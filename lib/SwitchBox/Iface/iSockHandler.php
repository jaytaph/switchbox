<?php

namespace SwitchBox\Iface;

use SwitchBox\SwitchBox;

interface iSockHandler {

    public function handle(SwitchBox $switchbox, $sock);
    public function getSelectSockets();

}
