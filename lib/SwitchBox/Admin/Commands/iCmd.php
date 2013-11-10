<?php

namespace SwitchBox\Admin\Commands;

use SwitchBox\SwitchBox;

interface iCmd {

    function execute(SwitchBox $switchbox, $sock, $args);
    function help();

}
