<?php

declare(strict_types=1);

namespace Jotup\Http\Dispatcher;

use Jotup\Container\Container;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;

class ControllerDispatcher
{
    public function __construct(
        private readonly Container $container,
        private readonly Responder $responder
    ) {
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function dispatch(string $controller, string $action, array $arguments = []): ResponseInterface
    {
        $instance = $this->container->make($controller);
        $result = $this->container->makeMethod($instance, $action, $arguments);

        return $this->responder->toResponse($result);
    }
}
