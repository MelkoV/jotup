<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Container\Container;
use Jotup\Contracts\Middleware;

class MiddlewareResolver
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * @param list<class-string|string|Middleware> $middleware
     * @return list<Middleware>
     */
    public function resolve(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $item) {
            if ($item instanceof Middleware) {
                $resolved[] = $item;
                continue;
            }

            if (!is_string($item) || $item === '') {
                continue;
            }

            $instance = $this->container->make($item);
            if ($instance instanceof Middleware) {
                $resolved[] = $instance;
            }
        }

        return $resolved;
    }
}
