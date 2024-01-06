<?php

namespace Reactphp\Framework\WebsocketMiddleware;

use React\Stream\DuplexStreamInterface;

/**
 * Wraps ConnectionInterface objects via the decorator pattern but allows
 * parameters to bubble through with magic methods
 * @todo It sure would be nice if I could make most of this a trait...
 */
 class Connection implements ConnectionInterface {
    /**
     * @var DuplexStreamInterface
     */
    protected $conn;

    public function __construct(DuplexStreamInterface $conn) 
    {
        $this->conn = $conn;
    }

    public function send($data) 
    {
        $this->conn->write($data);
        return $this;
    }

    public function close() 
    {
        return $this->conn->end();
    }

    public function getStream(): DuplexStreamInterface
    {
        return $this->conn;
    }

    
}