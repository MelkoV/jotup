<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public readonly RouteDefinition $route,
        public readonly array $parameters = [],
    ) {
    }
}
