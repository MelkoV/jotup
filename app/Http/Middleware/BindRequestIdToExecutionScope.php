<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Ramsey\Uuid\Uuid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class BindRequestIdToExecutionScope implements MiddlewareInterface
{
    public function __construct(
        private ExecutionScopeProviderInterface $executionScopeProvider,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = trim($request->getHeaderLine('X-Request-Id'));
        if ($requestId === '') {
            $requestId = Uuid::uuid7()->toString();
        }

        $this->executionScopeProvider->setRequestId($requestId);

        return $handler->handle($request);
    }
}
