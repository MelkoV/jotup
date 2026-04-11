<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Handler\CallableRequestHandler;
use Jotup\Http\Response\Responder;
use Jotup\Http\Routing\RouteDefinition;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatchMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ControllerDispatcher $dispatcher,
        private readonly Responder $responder,
        private readonly MiddlewareResolver $middlewareResolver
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute('route');

        if (!$route instanceof RouteDefinition) {
            return $handler->handle($request);
        }

        $finalHandler = new CallableRequestHandler(
            fn (ServerRequestInterface $request): ResponseInterface => $this->dispatchRoute($route, $request)
        );

        $routeMiddleware = $this->middlewareResolver->resolve($route->middleware);
        if ($routeMiddleware === []) {
            return $finalHandler->handle($request);
        }

        return (new MiddlewarePipeline($finalHandler, $routeMiddleware))->handle($request);
    }

    private function dispatchRoute(RouteDefinition $route, ServerRequestInterface $request): ResponseInterface
    {
        $handler = $route->handler;
        $params = $request->getAttribute('route_params', []);
        $arguments = is_array($params) ? array_values($params) : [];

        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $this->dispatcher->dispatch($handler[0], $handler[1], $arguments);
        }

        if (is_callable($handler)) {
            return $this->responder->toResponse($handler($request, ...$arguments));
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $action] = explode('@', $handler, 2);
            return $this->dispatcher->dispatch($controller, $action, $arguments);
        }

        return $this->responder->toResponse($handler);
    }
}
