<?php

declare(strict_types=1);

namespace App;

use Jotup\Container\Container;
use Psr\Log\LoggerInterface;

class Bootstrap implements \Jotup\Application\Bootstrap
{
    public function boot(Container $container): void
    {
        $logger = $container->get(LoggerInterface::class);
        $logger->debug('Bootstrap started');
//        var_dump(1/0);
        var_dump(true);
    }

    public function down(): void
    {

    }
}