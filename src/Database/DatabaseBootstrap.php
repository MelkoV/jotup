<?php

declare(strict_types=1);

namespace Jotup\Database;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Apcu\ApcuCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;

final class DatabaseBootstrap
{
    /**
     * @param array<string, mixed> $config
     */
    public static function createCache(array $config = []): CacheInterface
    {
        return new ApcuCache();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createSchemaCache(CacheInterface $cache, array $config = []): SchemaCache
    {
        $schemaCache = new SchemaCache($cache);
        $schemaCache->setEnabled((bool) ($config['enabled'] ?? true));
        $schemaCache->setDuration($config['duration'] ?? 3600);
        $schemaCache->setExclude($config['exclude'] ?? []);

        return $schemaCache;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createDriver(array $config): Driver
    {
        $driverConfig = $config['driver'] ?? [];
        $driver = new Driver(
            dsn: (string) ($driverConfig['dsn'] ?? ''),
            username: (string) ($driverConfig['username'] ?? ''),
            password: (string) ($driverConfig['password'] ?? ''),
            attributes: $driverConfig['attributes'] ?? [],
        );
        $driver->charset($driverConfig['charset'] ?? null);

        return $driver;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createConnection(
        array $config,
        SchemaCache $schemaCache,
        ?LoggerInterface $logger = null,
    ): ConnectionInterface {
        $connection = new Connection(
            self::createDriver($config),
            $schemaCache,
        );
        $connection->setTablePrefix((string) ($config['tablePrefix'] ?? ''));

        if ($logger !== null) {
            $connection->setLogger($logger);
        }

        return $connection;
    }
}
