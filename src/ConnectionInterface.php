<?php

namespace Reactphp\Framework\WebsocketMiddleware;

use React\Stream\DuplexStreamInterface;

/**
 * The version of Reactphp\Framework being used
 * @var string
 */
const VERSION = 'Reactphp\Framework/1.0.0';

/**
 * A proxy object representing a connection to the application
 * This acts as a container to store data (in memory) about the connection
 */
interface ConnectionInterface
{
    /**
     * Send data to the connection
     * @param  string $data
     * @return \Reactphp\Framework\WebsocketMiddleware\ConnectionInterface
     */
    function send($data);

    /**
     * Close the connection
     */
    function close();

    function getStream(): DuplexStreamInterface;
}
