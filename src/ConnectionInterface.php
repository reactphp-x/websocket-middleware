<?php

namespace ReactphpX\WebsocketMiddleware;

use React\Stream\DuplexStreamInterface;

/**
 * The version of ReactphpX being used
 * @var string
 */
const VERSION = 'ReactphpX/1.0.0';

/**
 * A proxy object representing a connection to the application
 * This acts as a container to store data (in memory) about the connection
 */
interface ConnectionInterface
{
    /**
     * Send data to the connection
     * @param  string $data
     * @return \ReactphpX\WebsocketMiddleware\ConnectionInterface
     */
    function send($data);

    /**
     * Close the connection
     */
    function close();

    function getStream(): DuplexStreamInterface;
}
