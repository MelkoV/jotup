<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Middleware\BindRequestIdToExecutionScope;
use Jotup\ExecutionScope\ExecutionScopeProvider;
use Jotup\Http\Factory\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

final class BindRequestIdToExecutionScopeTest extends TestCase
{
    public function testMiddlewareStoresExistingRequestIdInExecutionScope(): void
    {
        $scopeProvider = new ExecutionScopeProvider();
        $middleware = new BindRequestIdToExecutionScope($scopeProvider);

        $response = $middleware->process(
            (new HttpFactory())->createServerRequest('GET', '/')->withHeader('X-Request-Id', 'req-123'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return (new HttpFactory())->createResponse(204);
                }
            }
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('req-123', $scopeProvider->get()?->requestId);
    }

    public function testMiddlewareGeneratesRequestIdWhenHeaderIsMissing(): void
    {
        $scopeProvider = new ExecutionScopeProvider();
        $middleware = new BindRequestIdToExecutionScope($scopeProvider);

        $middleware->process(
            (new HttpFactory())->createServerRequest('GET', '/'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return (new HttpFactory())->createResponse(204);
                }
            }
        );

        $requestId = $scopeProvider->get()?->requestId;
        $this->assertIsString($requestId);
        $this->assertTrue(Uuid::isValid($requestId));
    }
}
