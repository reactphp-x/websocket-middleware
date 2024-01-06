<?php

namespace Reactphp\Framework\WebsocketMiddleware;

use Psr\Http\Message\ServerRequestInterface;
use Reactphp\Framework\WebsocketMiddleware\ConnectionInterface;
use Reactphp\Framework\WebsocketMiddleware\MessageComponentInterface;

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
