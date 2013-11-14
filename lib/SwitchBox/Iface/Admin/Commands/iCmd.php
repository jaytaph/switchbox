<?php

namespace SwitchBox\Iface\Admin\Commands;

use SwitchBox\SwitchBox;

interface iCmd {

    public function execute(SwitchBox $switchbox, $sock, $args);
    public function getHelp();

}
