<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

class Route
{
    private static ?RouteCollection $collection = null;
    /** @var list<array{prefix: string, middleware: list<class-string|string>}> */
    private static array $groupStack = [];

    public static function load(string $file, string $prefix = ''): RouteCollection
    {
        if (!class_exists('Route', false)) {
            class_alias(self::class, 'Route');
        }

        self::$collection = new RouteCollection();
        self::$groupStack = [[
            'prefix' => self::normalizePrefix($prefix),
            'middleware' => [],
        ]];

        require $file;

        return self::$collection;
    }

    /**
     * @param array{prefix?: string, middleware?: class-string|string|list<class-string|string>} $attributes
     */
    public static function group(array $attributes, callable $callback): void
    {
        $current = self::currentGroup();
        $next = [
            'prefix' => self::joinPaths($current['prefix'], $attributes['prefix'] ?? ''),
            'middleware' => [
                ...$current['middleware'],
                ...self::normalizeMiddleware($attributes['middleware'] ?? []),
            ],
        ];

        self::$groupStack[] = $next;
        $callback();
        array_pop(self::$groupStack);
    }

    public static function prefix(string $prefix): PendingRouteGroup
    {
        return new PendingRouteGroup(['prefix' => $prefix]);
    }

    /**
     * @param class-string|string|list<class-string|string> $middleware
     */
    public static function middleware(array|string $middleware): PendingRouteGroup
    {
        return new PendingRouteGroup([
            'middleware' => self::normalizeMiddleware($middleware),
        ]);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function get(string $path, array|string|callable $handler): void
    {
        self::add(['GET'], $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function post(string $path, array|string|callable $handler): void
    {
        self::add(['POST'], $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function put(string $path, array|string|callable $handler): void
    {
        self::add(['PUT'], $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function patch(string $path, array|string|callable $handler): void
    {
        self::add(['PATCH'], $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function delete(string $path, array|string|callable $handler): void
    {
        self::add(['DELETE'], $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function any(string $path, array|string|callable $handler): void
    {
        self::add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $handler);
    }

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string}|callable|string $handler
     */
    public static function add(array $methods, string $path, array|string|callable $handler): void
    {
        self::bootIfNeeded();
        $group = self::currentGroup();

        self::$collection?->add(new RouteDefinition(
            methods: array_map(static fn (string $method): string => strtoupper($method), $methods),
            path: self::joinPaths($group['prefix'], $path),
            handler: $handler,
            middleware: $group['middleware'],
        ));
    }

    /**
     * @param class-string|string|list<class-string|string> $middleware
     * @return list<class-string|string>
     */
    public static function normalizeMiddleware(array|string $middleware): array
    {
        if (is_string($middleware)) {
            return [$middleware];
        }

        return array_values($middleware);
    }

    public static function joinPaths(string $prefix, string $path): string
    {
        $prefix = trim($prefix);
        $path = trim($path);

        $full = rtrim($prefix, '/') . '/' . ltrim($path, '/');
        $full = preg_replace('#/+#', '/', $full) ?: '/';

        return $full === '' ? '/' : $full;
    }

    public static function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);

        if ($prefix === '' || $prefix === '/') {
            return '';
        }

        return '/' . trim($prefix, '/');
    }

    /**
     * @return array{prefix: string, middleware: list<class-string|string>}
     */
    private static function currentGroup(): array
    {
        self::bootIfNeeded();

        return self::$groupStack[array_key_last(self::$groupStack)];
    }

    private static function bootIfNeeded(): void
    {
        if (self::$collection !== null) {
            return;
        }

        self::$collection = new RouteCollection();
        self::$groupStack = [[
            'prefix' => '',
            'middleware' => [],
        ]];
    }
}
