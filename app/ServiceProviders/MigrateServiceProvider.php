<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use Jotup\Config;
use Jotup\Contracts\Application;
use Jotup\Provider\ServiceProvider;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;

final class MigrateServiceProvider implements ServiceProvider
{
    public function register(Application $application): void
    {
        $container = $application->getContainer();
        $migrationConfig = Config::get('migration');
        /** @var ConnectionInterface $connection */
        $connection = $container->get(ConnectionInterface::class);

        $injector = new Injector($container);
        $container->bind(Injector::class, $injector);

        $informer = new NullMigrationInformer();
        $container->bind(MigrationInformerInterface::class, $informer);

        $migrator = new Migrator(
            $connection,
            $informer,
            $migrationConfig['historyTable'] ?? '{{%migration}}',
            $migrationConfig['migrationNameLimit'] ?? 180,
            $migrationConfig['maxSqlOutputLength'] ?? null,
        );
        $container->bind(Migrator::class, $migrator);

        $migrationService = new MigrationService($connection, $injector, $migrator);
        $migrationService->setNewMigrationNamespace($migrationConfig['newMigrationNamespace'] ?? '');
        $migrationService->setNewMigrationPath($migrationConfig['newMigrationPath'] ?? '');
        $migrationService->setSourceNamespaces($migrationConfig['sourceNamespaces'] ?? []);
        $migrationService->setSourcePaths($migrationConfig['sourcePaths'] ?? []);

        $container->bind(MigrationService::class, $migrationService);
        $container->bind(
            id: CreateService::class,
            concrete: CreateService::class,
            singleton: true,
            values: [
                'db' => $connection,
                'useTablePrefix' => $migrationConfig['useTablePrefix'] ?? true,
            ],
        );
        $container->bind(DownRunner::class, DownRunner::class, true);
        $container->bind(UpdateRunner::class, UpdateRunner::class, true);
    }
}
