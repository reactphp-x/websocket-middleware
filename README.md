# reactphp-x-websocket-middleware

## install

```
composer require reactphp-x/websocket-middleware -vvv
```

## Usage

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use ReactphpX\WebsocketMiddleware\ConnectionInterface;
use ReactphpX\WebsocketMiddleware\MessageComponentInterface;

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyChat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn, ServerRequestInterface $request) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn, $reason = null) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$http = new React\Http\HttpServer(new WebsocketMiddleware(new MyChat()));
$socket = new React\Socket\SocketServer('0.0.0.0:8090');
$http->listen($socket);
```

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;
use ReactphpX\WebsocketMiddleware\EchoServer;
$http = new React\Http\HttpServer(new WebsocketMiddleware(new EchoServer()));
$socket = new React\Socket\SocketServer('0.0.0.0:8090');
$http->listen($socket);
```

require [clue/framework-x](https://github.com/clue/framework-x/)
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;
use ReactphpX\WebsocketMiddleware\EchoServer;

putenv("X_LISTEN=0.0.0.0:8090");

$app = new \FrameworkX\App();
$app->get('/echo', new WebsocketMiddleware(new EchoServer()));
$app->run();
```


front
```html
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <script>
        // Then some JavaScript in the browser:
        var conn = new WebSocket('ws://x.x.x.x:8090/echo');
        conn.onmessage = function (e) { console.log(e.data); };
        conn.onopen = function (e) { conn.send('Hello Me!'); };
    </script>
</body>

</html>
```

## License
MIT
