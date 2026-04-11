<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Container\Container;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareResolver
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * @param list<class-string|string|MiddlewareInterface> $middleware
     * @return list<MiddlewareInterface>
     */
    public function resolve(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $item) {
            if ($item instanceof MiddlewareInterface) {
                $resolved[] = $item;
                continue;
            }

            if (!is_string($item) || $item === '') {
                continue;
            }

            $instance = $this->container->make($item);
            if ($instance instanceof MiddlewareInterface) {
                $resolved[] = $instance;
            }
        }

        return $resolved;
    }
}
