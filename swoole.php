<?php

declare(strict_types=1);

use App\Bootstrap;
use Jotup\Application\Web;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Swoole\SwooleRequestFactory;
use Jotup\Http\Swoole\SwooleResponseEmitter;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;

require __DIR__ . '/vendor/autoload.php';

defined('APP_DEBUG') or define('APP_DEBUG', \Jotup\Env::getBool('APP_DEBUG'));
defined('APP_CORE_PATH') or define('APP_CORE_PATH', __DIR__ . DIRECTORY_SEPARATOR);

$host = \Jotup\Env::get('SWOOLE_HOST', '0.0.0.0');
$port = (int) \Jotup\Env::get('SWOOLE_PORT', '9501');

$server = new Server($host, $port);
$server->set([
    'worker_num' => (int) \Jotup\Env::get('SWOOLE_WORKER_NUM', '1'),
    'enable_coroutine' => true,
]);

$requestFactory = new SwooleRequestFactory();
$responseEmitter = new SwooleResponseEmitter();
$httpFactory = new HttpFactory();
$app = null;

$server->on('workerStart', static function () use (&$app): void {
    $app = new Web(new Bootstrap());
});

$server->on('request', static function (Request $request, Response $response) use (&$app, $requestFactory, $responseEmitter, $httpFactory): void {
    $app ??= new Web(new Bootstrap());

    try {
        $psrRequest = $requestFactory->createFromSwoole($request);
        $psrResponse = $app->handle($psrRequest);
    } catch (\Throwable $throwable) {
        error_log((string) $throwable);
        $psrResponse = $httpFactory
            ->createResponse(500)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($httpFactory->createStream(json_encode([
                'error' => APP_DEBUG ? $throwable->getMessage() : 'Internal Server Error',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    $responseEmitter->emit($response, $psrResponse);
});

$server->start();
