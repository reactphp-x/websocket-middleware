<?php

namespace Reactphp\Framework\WebsocketMiddleware;


interface MessageInterface {
    /**
     * Triggered when a client sends data through the socket
     * @param  \Reactphp\Framework\WebsocketMiddleware\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string                       $msg  The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg);
}