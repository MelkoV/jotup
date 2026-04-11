<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Contracts\WithMiddleware;
use Jotup\ErrorHandler;
use Jotup\Http\Handler\NotFoundHandler;
use Jotup\Http\HttpServiceProvider;
use Jotup\Http\Kernel;
use Jotup\Http\Middleware\DispatchMiddleware;
use Jotup\Http\Middleware\ExceptionMiddleware;
use Jotup\Http\Middleware\RoutingMiddleware;
use Jotup\Http\Request\ServerRequest;
use Jotup\Http\Response\Emitter;
use Jotup\Http\Routing\Route;
use Jotup\Http\Routing\RouteCollection;
use Jotup\Logger\Logger;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Web extends Base implements WithMiddleware
{
    /** @var MiddlewareInterface[] $middlewares */
    protected array $middleware = [];
    private ?RouteCollection $routeCollection = null;

    public function run(): void
    {
        $request = ServerRequest::fromGlobals();
        /** @var Emitter $emitter */
        $emitter = $this->container->get(Emitter::class);
        $kernel = new Kernel(
            notFoundHandler: $this->container->get(NotFoundHandler::class),
            exceptionMiddleware: $this->container->get(ExceptionMiddleware::class),
            routingMiddleware: $this->container->get(RoutingMiddleware::class),
            dispatchMiddleware: $this->container->get(DispatchMiddleware::class),
            middleware: $this->middleware,
        );
        $response = $kernel->handle($request);
        $emitter->emit($response);
    }

    /**
     * @param class-string $middleware
     * @return void
     */
    public function registerMiddleware(string $middleware): void
    {
        $this->container->bind($middleware, $middleware, true);
        $this->middleware[] = $this->container->get($middleware);
    }

    public function getRouteCollection(): RouteCollection
    {
        if ($this->routeCollection !== null) {
            return $this->routeCollection;
        }

        $collection = new RouteCollection();

        foreach ($this->bootstrap->routes() as $prefix => $file) {
            if (!is_string($file) || !is_file($file)) {
                continue;
            }

            $routePrefix = is_string($prefix) ? $prefix : '/';
            $loaded = Route::load($file, $routePrefix);
            foreach ($loaded->all() as $route) {
                $collection->add($route);
            }
        }

        return $this->routeCollection = $collection;
    }

    protected function registerServices(): void
    {
        new HttpServiceProvider()->register($this);
    }

    protected function registerBootstrapLogger(): void
    {
        $this->container->bind(
            id: LoggerInterface::class,
            concrete: Logger::class,
            singleton: true,
            values: ['routes' => [
                ['class' => \Jotup\Logger\Routes\Bootstrap::class, 'exclude' => [LogLevel::DEBUG]],
                ['class' => \Jotup\Logger\Routes\Stream::class, 'config' => ['stream' => 'php://stderr']],
            ]]
        );
    }

    protected function registerErrorHandlers(): void
    {
        $errorHandler = new ErrorHandler(function (): LoggerInterface {
            return $this->container->get(LoggerInterface::class);
        });
        $errorHandler->register(Config::get('error.level', E_ALL));
    }

}
