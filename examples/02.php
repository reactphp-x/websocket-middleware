<?php

require __DIR__ . '/../vendor/autoload.php';

use Reactphp\Framework\WebsocketMiddleware\WebsocketMiddleware;
use Reactphp\Framework\WebsocketMiddleware\EchoServer;

putenv("X_LISTEN=0.0.0.0:8090");

$app = new \FrameworkX\App();
$app->get('/echo', new WebsocketMiddleware(new EchoServer()));
$app->run();


