<?php

declare(strict_types=1);

namespace App;

use App\Console\Commands\AboutCommand;
use App\Console\Commands\DbSmokeCommand;
use App\Http\Middleware\AddDebugHeaders;
use App\Http\Middleware\BindRequestIdToExecutionScope;
use App\Http\Middleware\HandleCors;
use App\ServiceProviders\AppServiceProvider;
use App\ServiceProviders\DatabaseServiceProvider;
use App\ServiceProviders\LogServiceProvider;
use Jotup\Contracts\Application;
use Jotup\Contracts\WithCommands;
use Jotup\Contracts\WithMiddleware;

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
        $application->registerMiddleware(BindRequestIdToExecutionScope::class);
        $application->registerMiddleware(HandleCors::class);
        $application->registerMiddleware(AddDebugHeaders::class);
    }
}
