<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use ReactphpX\WebsocketMiddleware\ConnectionInterface;
use ReactphpX\WebsocketMiddleware\MessageComponentInterface;

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyChat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn, ServerRequestInterface $request) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn, $reason = null) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$http = new React\Http\HttpServer(new WebsocketMiddleware(new MyChat()));
$socket = new React\Socket\SocketServer('0.0.0.0:8090');
$http->listen($socket);


