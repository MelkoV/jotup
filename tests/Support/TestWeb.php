<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bootstrap;
use Jotup\Application\Web;
use Jotup\Http\Handler\NotFoundHandler;
use Jotup\Http\Kernel;
use Jotup\Http\Middleware\DispatchMiddleware;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Middleware\RoutingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TestWeb extends Web
{
    public function __construct()
    {
        parent::__construct(new Bootstrap());
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $kernel = new Kernel(
            notFoundHandler: $this->getContainer()->get(NotFoundHandler::class),
            exceptionMiddleware: $this->getContainer()->get(ExceptionMiddleware::class),
            routingMiddleware: $this->getContainer()->get(RoutingMiddleware::class),
            dispatchMiddleware: $this->getContainer()->get(DispatchMiddleware::class),
            middleware: $this->middleware,
        );

        return $kernel->handle($request);
    }
}
