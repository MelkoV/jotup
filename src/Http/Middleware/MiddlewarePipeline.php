<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Http\Handler\CallableRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private readonly RequestHandlerInterface $fallbackHandler,
        private array $middleware = []
    ) {
    }

    public function pipe(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;

        return $clone;
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function through(array $middleware): self
    {
        $clone = clone $this;
        $clone->middleware = $middleware;

        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->fallbackHandler;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $handler;
            $handler = new CallableRequestHandler(
                static fn (ServerRequestInterface $request): ResponseInterface => $middleware->process($request, $next)
            );
        }

        return $handler->handle($request);
    }
}
