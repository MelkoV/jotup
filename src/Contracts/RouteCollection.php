<?php

declare(strict_types=1);

namespace Jotup\Contracts;

interface RouteCollection
{
    public function all(): array;
}