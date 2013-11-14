<?php

namespace SwitchBox\Iface;

use SwitchBox\SwitchBox;

class Admin extends Sock {

    const DEFAULT_PROMPT  = "> ";

    protected $_prompt = self::DEFAULT_PROMPT;


    function __construct($tcp_port) {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);

        socket_bind($this->sock, 0, $tcp_port);

        socket_listen($this->sock, 1024);

        $this->sock_clients = array();
    }

    public function handle(SwitchBox $switchbox, $sock)
    {
        // Do initial socket connection
        if ($sock == $this->sock) {
            $this->_acceptSocket($sock);
        }

        // Do clients
        if (in_array($sock, $this->sock_clients)) {
            $this->_handleClientSock($switchbox, $sock);
        }
    }



    /**
     * @param string $prompt
     */
    public function setPrompt($prompt)
    {
        $this->_prompt = $prompt;
    }

    /**
     * @return string
     */
    public function getPrompt()
    {
        return $this->_prompt;
    }


    /**
     * @param $sock
     */
    protected function _acceptSocket($sock) {
        $sock = socket_accept($sock);
        $this->sock_clients[] = $sock;


        $buf = <<< EOB
     _       _      _               _
    | |     | |    | |             | |
    | |_ ___| | ___| |__   __ _ ___| |__
    | __/ _ \ |/ _ \ '_ \ / _` / __| '_ \
    | ||  __/ |  __/ | | | (_| \__ \ | | |
     \__\___|_|\___|_| |_|\__,_|___/_| |_|

EOB;

        $buf = ANSI_CYAN . $buf . ANSI_RESET;
        $this->_sock_write($sock, $buf);

        $buf = "\nWelcome to the TeleHash Admin Panel. \n" .
               "To quit, type 'quit', To seek help, type 'help'\n";
        $this->_sock_write($sock, $buf);

        $this->_sock_write($sock, $this->getPrompt());
    }


    /**
     * @param $sock
     */
    protected function _handleClientSock(SwitchBox $switchbox, $sock) {
        $s = socket_read($sock, 2048);
        $s = trim($s);

        $args = explode(" ", $s);
        $cmd = ucfirst(strtolower(array_shift($args)));

        // Check if class exists
        $class = "\\SwitchBox\\Iface\\Admin\\Commands\\".$cmd;
        if (class_exists($class)) {
            $cmd = new $class();
            /** @var $cmd iCmd */
            $cmd->execute($switchbox, $sock, $args);
        } else {
            $this->_sock_write($sock, "Unknown command ".$cmd.". Type 'help' for all available commands.\n");
        }

        // Display prompt only when we are still having an open TCP socket
        if (in_array($sock, $this->sock_clients)) {
            $this->_sock_write($sock, $this->getPrompt());
        }
    }

}
