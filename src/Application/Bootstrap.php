<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Container\Container;

interface Bootstrap
{
    public function boot(Container $container): void;

    public function down(): void;
}