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
            return $response;
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


        $inStream  = new ThroughStream();
        $outStream = new ThroughStream();
        $stream = new CompositeStream($outStream, $inStream);
        $connection = new Connection($stream);

        $response = new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $stream
        );

        $mb = new MessageBuffer(
            new CloseFrameChecker(),
            function (Message $message) use ($connection) {
                $this->component->onMessage($connection, $message->getPayload());
            },
            function (Frame $frame) use ($connection) {
                switch ($frame->getOpcode()) {
                    case Frame::OP_PING:
                        return;
                    case Frame::OP_PONG:;
                        break;
                    case Frame::OP_CLOSE:
                        $closeCode = unpack('n*', substr($frame->getPayload(), 0, 2));
                        $closeCode = reset($closeCode) ?: 1000;
                        $reason = '';

                        if ($frame->getPayloadLength() > 2) {
                            $reason = substr($frame->getPayload(), 2);
                        }
                        $this->component->onClose($connection, $reason);
                        break;
                }
            },
            true,
            null,
            $this->webSocketOptions->getMaxMessagePayloadSize(),
            $this->webSocketOptions->getMaxFramePayloadSize(),
            [$stream, 'write'],
            $permessageDeflateOptions[0]
        );

        $stream->on('data', [$mb, 'onData']);

        $this->component->onOpen($connection, $request);

        $response->getBody()->input->once('pipe', function ($con) use ($connection) {
            $con->on('error', function ($e) use ($connection) {
                $this->component->onError($connection, $e);
            });
        });

        return $response;
    }
}
