<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;
use ReactphpX\WebsocketMiddleware\EchoServer;

putenv("X_LISTEN=0.0.0.0:8090");

$app = new \FrameworkX\App();
$app->get('/echo', new WebsocketMiddleware(new EchoServer()));
$app->run();


