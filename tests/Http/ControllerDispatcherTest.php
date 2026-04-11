<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Bootstrap;
use App\Http\Api\Controllers\TestController;
use Jotup\Application\Web;
use Jotup\Container\Container;
use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerDispatcherTest extends TestCase
{
    public function testDispatcherInvokesActionWithMethodDi(): void
    {
        $container = new Container();
        $container->bind(DispatcherSharedService::class, DispatcherSharedService::class, true);

        $factory = new HttpFactory();
        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));
        $request = $factory->createServerRequest('GET', '/api/v1/test/42');

        $response = $dispatcher->dispatch(DispatcherTestController::class, 'show', [
            'id' => '42',
            'request' => $request,
        ]);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('42', $payload['id']);
        $this->assertSame(DispatcherSharedService::class, $payload['shared']);
        $this->assertSame('/api/v1/test/42', $payload['path']);
    }

    public function testDispatcherInjectsLoggerIntoRealControllerAction(): void
    {
        $application = new Web(new Bootstrap());
        $container = $application->getContainer();
        $dispatcher = $container->get(ControllerDispatcher::class);
        $request = (new HttpFactory())->createServerRequest('GET', '/api/v1/test');

        $response = $dispatcher->dispatch(TestController::class, 'index', [
            'request' => $request,
        ]);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['ok']);
        $this->assertSame('Public test route works', $payload['message']);

        restore_error_handler();
        restore_exception_handler();
    }
}

final class DispatcherSharedService
{
}

final class DispatcherTestController
{
    public function show(string $id, ServerRequestInterface $request, DispatcherSharedService $shared): array
    {
        return [
            'id' => $id,
            'shared' => $shared::class,
            'path' => $request->getUri()->getPath(),
        ];
    }
}
