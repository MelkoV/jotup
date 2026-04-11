<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

class RouteDefinition
{
    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string}|callable|string $handler
     * @param list<class-string|string> $middleware
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly mixed $handler,
        public readonly array $middleware = [],
    ) {
    }
}
