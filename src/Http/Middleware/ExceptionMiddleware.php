<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Contracts\Middleware;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExceptionMiddleware implements Middleware
{
    public function __construct(
        private readonly Responder $responder
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $throwable) {
            var_dump($throwable->getMessage());
            return $this->responder->fromThrowable($throwable);
        }
    }
}
