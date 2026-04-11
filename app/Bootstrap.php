<?php

declare(strict_types=1);

namespace App;

use App\Http\Middleware\AddDebugHeaders;
use App\ServiceProviders\AppServiceProvider;
use App\ServiceProviders\LogServiceProvider;
use Jotup\Contracts\Application;
use Jotup\Contracts\WithMiddleware;

class Bootstrap implements \Jotup\Contracts\Bootstrap
{
    public function getServiceProviders(): array
    {
        return [
            AppServiceProvider::class,
            LogServiceProvider::class,
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
        $application->getContainer()->get('logger')->debug('Bootstrap booting');
        $application->registerMiddleware(AddDebugHeaders::class);
    }

}