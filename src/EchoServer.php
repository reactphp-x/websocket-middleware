<?php

namespace ReactphpX\WebsocketMiddleware;

use Psr\Http\Message\ServerRequestInterface;
use ReactphpX\WebsocketMiddleware\ConnectionInterface;
use ReactphpX\WebsocketMiddleware\MessageComponentInterface;

class EchoServer implements MessageComponentInterface
{

    public function onOpen(ConnectionInterface $conn, ServerRequestInterface $request)
    {
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $from->send($msg);
    }

    public function onClose(ConnectionInterface $conn, $reason = null)
    {
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }
}
