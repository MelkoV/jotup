<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use Jotup\Config;
use Jotup\Contracts\Application;
use Jotup\Database\DatabaseBootstrap;
use Jotup\Database\DatabaseManager;
use Jotup\Database\Db;
use Jotup\Provider\ServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;

final class DatabaseServiceProvider implements ServiceProvider
{
    public function register(Application $application): void
    {
        $container = $application->getContainer();
        $dbConfig = Config::get('db');

        $cache = DatabaseBootstrap::createCache($dbConfig['cache'] ?? []);
        $schemaCache = DatabaseBootstrap::createSchemaCache($cache, $dbConfig['schemaCache'] ?? []);
        $logger = $container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : null;
        $connection = DatabaseBootstrap::createConnection($dbConfig, $schemaCache, $logger);

        $container->bind(CacheInterface::class, $cache);
        $container->bind(SchemaCache::class, $schemaCache);
        $container->bind(Driver::class, $connection->getDriver());
        $container->bind(Connection::class, $connection);
        $container->bind(ConnectionInterface::class, $connection);

        $databaseManager = new DatabaseManager($connection);
        $container->bind(DatabaseManager::class, $databaseManager);
        $container->bind(Db::class, new Db($databaseManager));
    }
}
