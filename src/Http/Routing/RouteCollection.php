<?php

declare(strict_types=1);

namespace Jotup\Http\Routing;

class RouteCollection implements \Jotup\Contracts\RouteCollection
{
    /** @var list<RouteDefinition> */
    private array $routes = [];

    public function add(RouteDefinition $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @return list<RouteDefinition>
     */
    public function all(): array
    {
        return $this->routes;
    }
}
