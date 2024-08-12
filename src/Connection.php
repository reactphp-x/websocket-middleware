<?php

namespace ReactphpX\WebsocketMiddleware;

use React\Stream\DuplexStreamInterface;
use Ratchet\RFC6455\Messaging\DataInterface;
use Ratchet\RFC6455\Messaging\Frame;

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

    public function close($code = 1000) 
    {
        if (!$this->conn->isWritable()) {
            return ;
        }

        if ($code instanceof DataInterface) {
            $this->send($code);
        } else {
            $this->send(new Frame(pack('n', $code), true, Frame::OP_CLOSE));
        }

        return $this->conn->end();
    }

    public function getStream(): DuplexStreamInterface
    {
        return $this->conn;
    }

    
}