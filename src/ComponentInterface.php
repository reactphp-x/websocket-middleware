<?php

namespace Reactphp\Framework\WebsocketMiddleware;

use Psr\Http\Message\ServerRequestInterface;

/**
 * This is the interface to build a Ratchet application with.
 * It implements the decorator pattern to build an application stack
 */
interface ComponentInterface {
    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @param  ServerRequestInterface $request The request
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn, ServerRequestInterface $request);

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @param  string $reason The socket/connection closing/closed $reason
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn, $reason = null);

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception          $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e);
}