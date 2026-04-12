<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\AboutCommand;
use App\Console\Commands\DbSmokeCommand;
use App\ServiceProviders\AppServiceProvider;
use App\ServiceProviders\DatabaseServiceProvider;
use App\ServiceProviders\LogServiceProvider;
use App\ServiceProviders\MigrateServiceProvider;
use Jotup\Contracts\Application;
use Jotup\Contracts\WithCommands;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;

class Bootstrap implements \Jotup\Contracts\Bootstrap
{

    public function boot(Application|WithCommands $application): void
    {
        $application->registerCommand(AboutCommand::class);
        $application->registerCommand(DbSmokeCommand::class);
    }

    public function getServiceProviders(): array
    {
        return [
            AppServiceProvider::class,
            LogServiceProvider::class,
            DatabaseServiceProvider::class,
            MigrateServiceProvider::class,
        ];
    }

    public function routes(): array
    {
        return [
            AboutCommand::class,
            DbSmokeCommand::class,
            CreateCommand::class,
            DownCommand::class,
            HistoryCommand::class,
            NewCommand::class,
            RedoCommand::class,
            UpdateCommand::class,
        ];
    }
}
