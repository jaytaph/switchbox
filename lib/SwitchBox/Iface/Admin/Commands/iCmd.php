<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\Iface\SockHandler;
use SwitchBox\SwitchBox;

interface iCmd {

    public function execute(SwitchBox $switchbox, SockHandler $handler, $sock, $args);
    public function getHelp();

}
