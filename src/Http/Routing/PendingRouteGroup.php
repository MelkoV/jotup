<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

class PendingRouteGroup
{
    /**
     * @param array{prefix?: string, middleware?: list<class-string|string>} $attributes
     */
    public function __construct(
        private readonly array $attributes = []
    ) {
    }

    /**
     * @param class-string|string|list<class-string|string> $middleware
     */
    public function middleware(array|string $middleware): self
    {
        $clone = clone $this;
        $existing = $clone->attributes['middleware'] ?? [];
        $clone->attributes['middleware'] = [
            ...$existing,
            ...Route::normalizeMiddleware($middleware),
        ];

        return $clone;
    }

    public function prefix(string $prefix): self
    {
        $clone = clone $this;
        $clone->attributes['prefix'] = Route::joinPaths($clone->attributes['prefix'] ?? '', $prefix);

        return $clone;
    }

    public function group(callable $callback): void
    {
        Route::group($this->attributes, $callback);
    }
}
