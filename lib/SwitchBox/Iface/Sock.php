<?php

namespace SwitchBox\Iface;

abstract class Sock implements iSockHandler {
    protected $sock_clients = array();
    protected $sock;

    public function closeSock($sock) {
        $i = array_search($sock, $this->sock_clients);
        if ($i !== false) {
            unset($this->sock_clients[$i]);
            socket_close($sock);
        }
    }

    protected function _sock_write($sock, $str) {
        $ret = socket_write($sock, $str, strlen($str));
        if ($ret != strlen($str)) {
            throw new \Exception("Something went wrong");
        }
    }

    public function getSelectSockets()
    {
        return array_merge(array($this->sock), $this->sock_clients);
    }

}
