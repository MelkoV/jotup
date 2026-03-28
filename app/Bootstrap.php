<?php

declare(strict_types=1);

namespace App;

use Jotup\Config;
use Jotup\DB\Connections\Connection;
use Jotup\DI\Container;
use Psr\Log\LoggerInterface;

class Bootstrap implements \Jotup\Application\Bootstrap
{
    public function boot(): void
    {
        $logger = Container::get(LoggerInterface::class);
//        $logger->info('Test');
//        Container::bindComponent('db', Config::get('db'));
//        Container::bind(Connection::class, Config::get('db.class'), values: Config::get('db'));
    }

    public function down(): void
    {

    }
}