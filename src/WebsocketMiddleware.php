<?php

namespace Reactphp\Framework\WebsocketMiddleware;

use Psr\Http\Message\ServerRequestInterface;
use Ratchet\RFC6455\Handshake\PermessageDeflateOptions;
use Ratchet\RFC6455\Handshake\RequestVerifier;
use Ratchet\RFC6455\Handshake\ServerNegotiator;
use React\Http\Message\Response;
use React\Stream\CompositeStream;
use React\Stream\ThroughStream;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;
use Ratchet\RFC6455\Messaging\MessageBuffer;
use React\Stream\Util;
use React\EventLoop\Loop;

class WebsocketMiddleware
{
    private $component = null;
    private $subProtocols;
    private $webSocketOptions = null;

    public function __construct(MessageComponentInterface $component, array $subProtocols = [], WebSocketOptions $options = null)
    {
        $this->component = $component;
        $this->subProtocols = $subProtocols;
        $this->webSocketOptions = $options ?: WebSocketOptions::getDefault();
    }

    public function __invoke(ServerRequestInterface $request, $next = null)
    {
        $negotiator = new ServerNegotiator(new RequestVerifier(), $this->webSocketOptions->isPermessageDeflateEnabled());
        $negotiator->setSupportedSubProtocols($this->subProtocols);
        $negotiator->setStrictSubProtocolCheck(true);

        $response = $negotiator->handshake($request);

        if ($response->getStatusCode() !== 101) {
            return Response::html(
                "<center><h1>Http Request</h1></center><hr><center>reactphp-framework/websocket-middleware 1.0.0</center>",
            );
        }

        if (!$this->webSocketOptions->isPermessageDeflateEnabled()) {
            $permessageDeflateOptions = [
                PermessageDeflateOptions::createDisabled()
            ];
        } else {
            try {
                $permessageDeflateOptions = PermessageDeflateOptions::fromRequestOrResponse($request);
            } catch (\Exception $e) {
                // 500 - Internal server error
                return new Response(500, [], 'Error negotiating websocket permessage-deflate: ' . $e->getMessage());
            }
        }

        $mb = null;
        $middleInStream  = new ThroughStream();
        $middleOutStream = new ThroughStream(function ($data) use (&$mb) {
            if ($data instanceof Frame) {
                $mb->sendFrame($data);
            } else if ($data instanceof MessageInterface) {
                $mb->sendMessage($data->getPayload(), true, $data->isBinary());
            } else {
                $mb->sendMessage($data);
            }
        });
        $middleStream = new CompositeStream($middleInStream, $middleOutStream);
        $connection = new Connection($middleStream);

        $inStream  = new ThroughStream();
        $outStream = new ThroughStream();
        $response = new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            new CompositeStream(
                $outStream,
                $inStream
            )
        );

        $mb = new MessageBuffer(
            new CloseFrameChecker(),
            function (Message $message) use ($connection) {
                // 为了$middleStream 触发读事件
                $connection->getStream()->emit('data', [$message->getPayload()]);
                $this->component->onMessage($connection, $message->getPayload());
            },
            function (Frame $frame) use ($connection) {
                switch ($frame->getOpcode()) {
                    case Frame::OP_PING:
                        break;
                    case Frame::OP_PONG:
                        break;
                    case Frame::OP_CLOSE:
                        $closeCode = unpack('n*', substr($frame->getPayload(), 0, 2));
                        $closeCode = reset($closeCode) ?: 1000;
                        $reason = '';

                        if ($frame->getPayloadLength() > 2) {
                            $reason = substr($frame->getPayload(), 2);
                        }
                        // $this->component->onClose($connection, $reason);
                        break;
                }
            },
            true,
            null,
            $this->webSocketOptions->getMaxMessagePayloadSize(),
            $this->webSocketOptions->getMaxFramePayloadSize(),
            [$outStream, 'write'],
            $permessageDeflateOptions[0]
        );

        $inStream->on('data', [$mb, 'onData']);

        Loop::futureTick(function () use ($connection, $request) {
            $this->component->onOpen($connection, $request);
        });

        $response->getBody()->input->once('pipe', function ($con) use ($connection) {

            $connection->getStream()->on('error', function ($e) use ($con) {
                $con->emit('error', [$e]);
            });

            $connection->getStream()->on('close', function () use ($con) {
                $con->close();
            });

            $con->on('error', function ($e) use ($connection) {
                $connection->getStream()->emit('error', [$e]);
                $this->component->onError($connection, $e);
            });
            
            $con->on('close', function () use ($connection, $con) {
                $connection->getStream()->close();
                $this->component->onClose($connection);
            });
        });

        return $response;
    }
}
