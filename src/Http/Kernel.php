<?php

declare(strict_types=1);

namespace Jotup\Http;

use Jotup\Contracts\Middleware;
use Jotup\Http\Handler\NotFoundHandler;
use Jotup\Http\Middleware\DispatchMiddleware;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Middleware\MiddlewarePipeline;
use Jotup\Http\Middleware\RoutingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Kernel
{
    /**
     * @param Middleware[] $middleware
     */
    public function __construct(
        private readonly NotFoundHandler $notFoundHandler,
        private readonly ExceptionMiddleware $exceptionMiddleware,
        private readonly RoutingMiddleware $routingMiddleware,
        private readonly DispatchMiddleware $dispatchMiddleware,
        private readonly array $middleware = [],
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new MiddlewarePipeline(
            $this->notFoundHandler,
            [
                $this->exceptionMiddleware,
                ...$this->middleware,
                $this->routingMiddleware,
                $this->dispatchMiddleware,
            ]
        );

        return $pipeline->handle($request);
    }
}
