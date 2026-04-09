<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$server = new OpenSwoole\HTTP\Server("0.0.0.0", 9501);
$server->on("request", function ($request, $response) {
    $response->end("Hello World\n");
});
$server->start();