<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

use Psr\Http\Message\ServerRequestInterface;

class RouteMatcher
{
    public function __construct(
        private readonly RouteCollection $routes
    ) {
    }

    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        $method = strtoupper($request->getMethod());
        $path = $this->normalizePath($request->getUri()->getPath() ?: '/');

        foreach ($this->routes->all() as $route) {
            if (!in_array($method, $route->methods, true)) {
                continue;
            }

            $routePath = $this->normalizePath($route->path);
            $pattern = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
                $routePath
            );

            if ($pattern === null) {
                continue;
            }

            if (!preg_match('#^' . $pattern . '$#', $path, $matches)) {
                continue;
            }

            $parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $parameters[$key] = $value;
                }
            }

            return new RouteMatch($route, $parameters);
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return rtrim($path, '/');
    }
}
