<?php

declare(strict_types=1);

namespace Tests\Http;

use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Handler\NotFoundHandler;
use Jotup\Http\Response\Respond;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;

final class NotFoundHandlerTest extends TestCase
{
    public function testHandlerReturnsJson404Response(): void
    {
        $factory = new HttpFactory();
        $handler = new NotFoundHandler(
            new Respond(),
            new Responder($factory, $factory),
        );

        $response = $handler->handle($factory->createServerRequest('GET', '/unknown'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('"error": "Not Found"', (string) $response->getBody());
    }
}
