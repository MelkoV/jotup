<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Bootstrap;
use App\Repositories\UserRepository;
use Jotup\Application\Web;
use Jotup\Database\DatabaseManager;
use Jotup\Database\Db;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

final class DatabaseServiceProviderTest extends TestCase
{
    public function testDatabaseServicesAreRegisteredInContainer(): void
    {
        $application = new Web(new Bootstrap());
        $container = $application->getContainer();

        $cache = $container->get(CacheInterface::class);
        $schemaCache = $container->get(SchemaCache::class);
        $connection = $container->get(ConnectionInterface::class);
        $databaseManager = $container->get(DatabaseManager::class);
        $db = $container->get(Db::class);
        $migrator = $container->get(Migrator::class);
        $migrationService = $container->get(MigrationService::class);
        $userRepository = $container->make(UserRepository::class);

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertInstanceOf(SchemaCache::class, $schemaCache);
        $this->assertInstanceOf(ConnectionInterface::class, $connection);
        $this->assertInstanceOf(DatabaseManager::class, $databaseManager);
        $this->assertInstanceOf(Db::class, $db);
        $this->assertInstanceOf(Migrator::class, $migrator);
        $this->assertInstanceOf(MigrationService::class, $migrationService);
        $this->assertInstanceOf(UserRepository::class, $userRepository);
        $this->assertSame($connection, $databaseManager->connection());
        $this->assertSame($connection, $db->connection());

        restore_error_handler();
        restore_exception_handler();
    }

    public function testMigrationConfigBootstrapBuildsConnectionAndPaths(): void
    {
        /** @var array<string, mixed> $config */
        $config = require APP_CORE_PATH . 'yii-db-migration.php';

        $this->assertInstanceOf(ConnectionInterface::class, $config['db']);
        $this->assertSame(APP_CORE_PATH . 'database' . DIRECTORY_SEPARATOR . 'Migrations', $config['newMigrationPath']);
        $this->assertSame([APP_CORE_PATH . 'database' . DIRECTORY_SEPARATOR . 'Migrations'], $config['sourcePaths']);
        $this->assertNull($config['container']);
    }

    public function testImportedMigrationFilesExist(): void
    {
        $path = APP_CORE_PATH . 'database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR;

        $this->assertFileExists($path . 'M260411120000CreateUsersTable.php');
        $this->assertFileExists($path . 'M260411120003CreateListsTable.php');
    }
}
