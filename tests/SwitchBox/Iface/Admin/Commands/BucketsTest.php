<?php


class SwitchBox_Iface_Admin_Commands_BucketsTest extends PHPUnit_Framework_TestCase {

    public function testGetHelp() {
        $cmd = new \SwitchBox\Iface\Admin\Commands\Buckets();
        $this->assertCount(3, $cmd->getHelp());
    }
}
