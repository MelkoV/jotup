<?php

declare(strict_types=1);

namespace Jotup\Http\Handler;

use Jotup\Http\Response\Respond;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExceptionHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Respond $respond,
        private readonly Responder $responder
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responder->toResponse(
            $this->respond->json([
                'ok' => false,
                'error' => 'Internal Server Error',
                'status' => 500,
            ], 500)
        );
    }
}
