<?php

declare(strict_types=1);

namespace Jotup\Contracts;

use Psr\Http\Server\MiddlewareInterface;

interface WithMiddleware
{
    public function registerMiddleware(string $middleware);
}