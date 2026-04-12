<?php

declare(strict_types=1);

namespace App;

use App\Console\Commands\AboutCommand;
use App\Console\Commands\DbSmokeCommand;
use App\Http\Middleware\AddDebugHeaders;
use App\Http\Middleware\HandleCors;
use App\ServiceProviders\AppServiceProvider;
use App\ServiceProviders\DatabaseServiceProvider;
use App\ServiceProviders\LogServiceProvider;
use App\ServiceProviders\MigrateServiceProvider;
use Jotup\Contracts\Application;
use Jotup\Contracts\WithCommands;
use Jotup\Contracts\WithMiddleware;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;

class Bootstrap implements \Jotup\Contracts\Bootstrap
{
    public function getServiceProviders(): array
    {
        return [
            AppServiceProvider::class,
            LogServiceProvider::class,
            DatabaseServiceProvider::class,
        ];
    }

    public function routes(): array
    {
        return [
            'api' => APP_CORE_PATH . 'routes/api.php',
        ];
    }

    public function boot(Application|WithMiddleware $application): void
    {
        if ($application instanceof WithMiddleware) {
            $application->registerMiddleware(HandleCors::class);
            $application->registerMiddleware(AddDebugHeaders::class);
        }

        if ($application instanceof WithCommands) {
            $application->registerCommand(AboutCommand::class);
            $application->registerCommand(DbSmokeCommand::class);
            $application->registerCommand(CreateCommand::class);
            $application->registerCommand(DownCommand::class);
            $application->registerCommand(HistoryCommand::class);
            $application->registerCommand(NewCommand::class);
            $application->registerCommand(RedoCommand::class);
            $application->registerCommand(UpdateCommand::class);
        }
    }
}
