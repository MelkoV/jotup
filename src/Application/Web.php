<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Contracts\WithMiddleware;
use Jotup\ErrorHandler;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

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
        $response = $this->handle($request);
        $emitter->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->clearExecutionScope();

        try {
            return $this->createKernel()->handle($request);
        } finally {
            $this->clearExecutionScope();
        }
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

    protected function createKernel(): Kernel
    {
        return new Kernel(
            notFoundHandler: $this->container->get(NotFoundHandler::class),
            exceptionMiddleware: $this->container->get(ExceptionMiddleware::class),
            routingMiddleware: $this->container->get(RoutingMiddleware::class),
            dispatchMiddleware: $this->container->get(DispatchMiddleware::class),
            middleware: $this->middleware,
        );
    }

    protected function registerServices(): void
    {
        new HttpServiceProvider()->register($this);
    }

    protected function registerBootstrapLogger(): void
    {
        if (defined('APP_TESTING') && APP_TESTING) {
            $this->container->bind(LoggerInterface::class, new NullLogger());

            return;
        }

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
        $errorHandler = new ErrorHandler(
            fn (): LoggerInterface => $this->container->get(LoggerInterface::class),
            (bool) Config::get('error.ignoreVendorDeprecations', false),
        );
        $errorHandler->register(Config::get('error.level', E_ALL));
    }

    private function clearExecutionScope(): void
    {
        if (!$this->container->has(ExecutionScopeProviderInterface::class)) {
            return;
        }

        $this->container->get(ExecutionScopeProviderInterface::class)->clear();
    }

}
