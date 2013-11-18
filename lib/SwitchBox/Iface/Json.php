<?php

namespace SwitchBox\Iface;

use SwitchBox\SwitchBox;

class Json extends SockHandler {

    /** @var SwitchBox */
    protected $switchbox;


    /**
     * @param SwitchBox $switchbox
     * @param $tcp_port
     */
    public function __construct(SwitchBox $switchbox, $tcp_port) {
        // Setup TCP command socket
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);

        socket_bind($this->sock, 0, $tcp_port);
        socket_listen($this->sock, 1024);

        $this->switchbox = $switchbox;

        $this->sock_clients = array();
    }


    /**
     * @param SwitchBox $switchbox
     * @param $sock
     */
    public function handle(SwitchBox $switchbox, $sock)
    {
        // Do initial socket connection
        if ($sock == $this->sock) {
            $this->_acceptSocket($sock);
        }

        // Do clients
        if (in_array($sock, $this->sock_clients)) {
            $this->_handleClientSock($sock);
        }
    }


    /**
     * @param $sock
     */
    protected function _acceptSocket($sock) {
        $sock = socket_accept($sock);
        $this->sock_clients[] = $sock;
    }


    /**
     * @param $sock
     */
    protected function _handleClientSock($sock) {
        $json = socket_read($sock, 2048);
        $json = trim($json);
        $json = json_decode($json, true);

        // Check if class exists
        $class = "\\SwitchBox\\Iface\\Json\\Commands\\".ucfirst(strtolower($json['c']));
        if (class_exists($class)) {
            $cmd = new $class();
            $buf = json_encode($cmd->execute($this, $sock, $json));
        } else {
            $buf = json_encode(array('err' => "Unknown command: ".$json['c']));
        }
        $this->sock_write($sock, $buf);
    }

}
