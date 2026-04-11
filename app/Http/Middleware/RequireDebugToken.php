<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Jotup\Http\Response\Respond;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequireDebugToken implements MiddlewareInterface
{
    public function __construct(
        private readonly Respond $respond,
        private readonly Responder $responder
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeaderLine('X-Debug-Token') !== 'letmein') {
            return $this->responder->toResponse(
                $this->respond->json([
                    'ok' => false,
                    'error' => 'Missing or invalid X-Debug-Token header',
                ], 401)
            );
        }

        return $handler->handle($request);
    }
}
