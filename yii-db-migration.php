<?php

declare(strict_types=1);

use Jotup\Config;
use Jotup\Database\DatabaseBootstrap;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

defined('APP_CORE_PATH') or define('APP_CORE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('APP_DEBUG') or define('APP_DEBUG', \Jotup\Env::getBool('APP_DEBUG'));

$dbConfig = Config::get('db');
$migrationConfig = Config::get('migration');

$cache = DatabaseBootstrap::createCache($dbConfig['cache'] ?? []);
$schemaCache = DatabaseBootstrap::createSchemaCache($cache, $dbConfig['schemaCache'] ?? []);
$connection = DatabaseBootstrap::createConnection($dbConfig, $schemaCache);

/**
 * @var array{
 *     historyTable?: string,
 *     migrationNameLimit?: int|null,
 *     maxSqlOutputLength?: int|null,
 *     useTablePrefix?: bool,
 *     newMigrationNamespace?: string,
 *     newMigrationPath?: string,
 *     sourceNamespaces?: list<string>,
 *     sourcePaths?: list<string>,
 * } $migrationConfig
 */

return [
    'db' => $connection,
    'container' => null,
    'historyTable' => $migrationConfig['historyTable'] ?? '{{%migration}}',
    'migrationNameLimit' => $migrationConfig['migrationNameLimit'] ?? 180,
    'maxSqlOutputLength' => $migrationConfig['maxSqlOutputLength'] ?? null,
    'useTablePrefix' => $migrationConfig['useTablePrefix'] ?? true,
    'newMigrationNamespace' => $migrationConfig['newMigrationNamespace'] ?? '',
    'newMigrationPath' => $migrationConfig['newMigrationPath'] ?? '',
    'sourceNamespaces' => $migrationConfig['sourceNamespaces'] ?? [],
    'sourcePaths' => $migrationConfig['sourcePaths'] ?? [],
];
