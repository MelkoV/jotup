<?php

declare(strict_types=1);

namespace Jotup\Http;

use Jotup\Contracts\Application;
use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Handler\ExceptionHandler;
use Jotup\Http\Handler\NotFoundHandler;
use Jotup\Http\Middleware\DispatchMiddleware;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Middleware\MiddlewareResolver;
use Jotup\Http\Middleware\RoutingMiddleware;
use Jotup\Http\Response\Emitter;
use Jotup\Http\Response\Respond;
use Jotup\Http\Response\Responder;
use Jotup\Http\Routing\RouteCollection;
use Jotup\Http\Routing\RouteMatcher;
use Jotup\Provider\ServiceProvider;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpServiceProvider implements ServiceProvider
{

    public function register(Application $application): void
    {
        $container = $application->getContainer();
        $httpFactory = $container->bind(HttpFactory::class, HttpFactory::class, true);
        $container->bind(RouteCollection::class, $application->getRouteCollection());
        $container->bind(RequestFactoryInterface::class, $httpFactory);
        $container->bind(ResponseFactoryInterface::class, $httpFactory);
        $container->bind(ServerRequestFactoryInterface::class, $httpFactory);
        $container->bind(StreamFactoryInterface::class, $httpFactory);
        $container->bind(UploadedFileFactoryInterface::class, $httpFactory);
        $container->bind(UriFactoryInterface::class, $httpFactory);
        $container->bind(Emitter::class, Emitter::class, true);
        $container->bind(Respond::class, Respond::class, true);
        $container->bind(Responder::class, Responder::class, true);
        $container->bind(ExceptionHandler::class, ExceptionHandler::class, true);
        $container->bind(NotFoundHandler::class, NotFoundHandler::class, true);
        $container->bind(MiddlewareResolver::class, new MiddlewareResolver($container), true);
        $container->bind(ExceptionMiddleware::class, ExceptionMiddleware::class, true);
        $container->bind(RoutingMiddleware::class, RoutingMiddleware::class, true);
        $container->bind(DispatchMiddleware::class, DispatchMiddleware::class, true);
        $container->bind(RouteMatcher::class, RouteMatcher::class, true);
//        $pipe = $container->bind(MiddlewarePipeline::class, MiddlewarePipeline::class, values: [
//            'fallbackHandler' => $container->get(NotFoundHandler::class),
//        ]);
//        $container->bind(RequestHandlerInterface::class, $pipe);
        $container->bind(ControllerDispatcher::class, ControllerDispatcher::class, true);

    }
}
