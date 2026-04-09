<?php

declare(strict_types=1);

namespace App;

use Jotup\Config;
use Jotup\Container\Container;
use Psr\Log\LoggerInterface;

class Bootstrap implements \Jotup\Application\Bootstrap
{
    public function boot(Container $container): void
    {
        $container->bind(LoggerInterface::class, Config::get('log.class'), values: ['routes' => Config::get('log.routes')]);
    }

    public function down(): void
    {

    }
}