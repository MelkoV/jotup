<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Contracts\Middleware;
use Jotup\Http\Routing\RouteMatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddleware implements Middleware
{
    public function __construct(
        private readonly RouteMatcher $matcher
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $match = $this->matcher->match($request);

        if ($match !== null) {
            $request = $request->withAttribute('route', $match->route);
            $request = $request->withAttribute('route_params', $match->parameters);

            foreach ($match->parameters as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }

        return $handler->handle($request);
    }
}
