<?php

namespace Switchbox\Comm\Commands;

class Info {

    public function execute($switchbox, $sock, $json) {

        $ret = array(
            'uptime' => time() - $switchbox->getStartTime(),
            'connected_peers' => count($switchbox->getMesh()->getConnectedNodes()),
        );
        return $ret;
    }

}
