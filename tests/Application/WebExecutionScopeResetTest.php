<?php

declare(strict_types=1);

namespace Tests\Application;

use Jotup\ExecutionScope\ExecutionScope;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Jotup\Http\Factory\HttpFactory;
use Tests\Support\TestWeb;
use PHPUnit\Framework\TestCase;

final class WebExecutionScopeResetTest extends TestCase
{
    protected function tearDown(): void
    {
        restore_exception_handler();
        restore_error_handler();

        parent::tearDown();
    }

    public function testApplicationClearsExecutionScopeBetweenRequests(): void
    {
        $app = new TestWeb();
        $scopeProvider = $app->getContainer()->get(ExecutionScopeProviderInterface::class);
        $scopeProvider->set(new ExecutionScope('user-before', 'request-before'));

        $response = $app->handleRequest(
            (new HttpFactory())
                ->createServerRequest('GET', '/api/unknown', [
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/api/unknown',
                    'SERVER_PROTOCOL' => 'HTTP/1.1',
                    'HTTP_HOST' => 'localhost',
                ])
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertNull($scopeProvider->get());
    }
}
