<?php

namespace SwitchBox;

interface iSockHandler {

    public function handle(SwitchBox $switchbox, $sock);
    public function getSelectSockets();

}
