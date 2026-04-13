<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Exceptions\RecordNotFoundException;
use Jotup\Http\Exception\HttpException;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;

final class ExceptionMiddlewareTest extends TestCase
{
    public function testUnexpectedExceptionIsLoggedAndConvertedToJson500Response(): void
    {
        $factory = new HttpFactory();
        $middleware = new ExceptionMiddleware(
            new Responder($factory, $factory),
            $logger = new InMemoryLogger(),
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', '/fail'),
            new ThrowingHandler(new \RuntimeException('Boom')),
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('"error": "Boom"', (string) $response->getBody());
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertSame('Unhandled exception while processing request', $logger->records[0]['message']);
    }

    public function testHttpExceptionKeepsItsOwnStatusCode(): void
    {
        $factory = new HttpFactory();
        $middleware = new ExceptionMiddleware(
            new Responder($factory, $factory),
            new InMemoryLogger(),
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', '/missing'),
            new ThrowingHandler(new HttpException(404, 'Missing')),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('"message": "Missing"', (string) $response->getBody());
    }

    public function testRecordNotFoundExceptionIsConvertedToJson404Response(): void
    {
        $factory = new HttpFactory();
        $middleware = new ExceptionMiddleware(
            new Responder($factory, $factory),
            new InMemoryLogger(),
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', '/missing-record'),
            new ThrowingHandler(new RecordNotFoundException('Record missing')),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('"message": "Record missing"', (string) $response->getBody());
    }
}

final class ThrowingHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly \Throwable $throwable,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw $this->throwable;
    }
}

final class InMemoryLogger extends AbstractLogger
{
    /** @var array<int, array{level:string,message:string,context:array}> */
    public array $records = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
