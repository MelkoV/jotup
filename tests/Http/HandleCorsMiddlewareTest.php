<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Middleware\HandleCors;
use Jotup\Http\Exception\ValidationException;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Middleware\MiddlewarePipeline;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;

final class HandleCorsMiddlewareTest extends TestCase
{
    public function testPreflightRequestReturnsCorsHeadersWithoutCallingNextHandler(): void
    {
        $factory = new HttpFactory();
        $middleware = new HandleCors(new Responder($factory, $factory));

        $request = $factory->createServerRequest('OPTIONS', '/api/v1/user/sign-in')
            ->withHeader('Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->withHeader('Access-Control-Request-Headers', 'Content-Type, Authorization');

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Preflight request should not hit next handler.');
            }
        });

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertSame('POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('Content-Type, Authorization', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testNonApiPathPassesThroughWithoutCorsHeaders(): void
    {
        $factory = new HttpFactory();
        $middleware = new HandleCors(new Responder($factory, $factory));

        $request = $factory->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $response = $middleware->process($request, new class($factory) implements RequestHandlerInterface {
            public function __construct(
                private readonly HttpFactory $factory,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testCorsHeadersAreAddedToValidationErrorResponses(): void
    {
        $factory = new HttpFactory();
        $responder = new Responder($factory, $factory);
        $pipeline = new MiddlewarePipeline(
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    throw new \RuntimeException('Should not reach fallback handler.');
                }
            },
            [
                new HandleCors($responder),
                new ExceptionMiddleware($responder, new class extends AbstractLogger {
                    public function log($level, \Stringable|string $message, array $context = []): void
                    {
                    }
                }),
                new class implements \Psr\Http\Server\MiddlewareInterface {
                    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                    {
                        throw new ValidationException([
                            'email' => ['auth.failed'],
                        ], 'auth.failed');
                    }
                },
            ]
        );

        $request = $factory->createServerRequest('POST', '/api/v1/user/sign-in')
            ->withHeader('Origin', 'http://localhost:3000');

        $response = $pipeline->handle($request);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('auth.failed', $payload['message']);
    }
}
