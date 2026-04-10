<?php

declare(strict_types=1);

namespace App;

use App\ServiceProviders\AppServiceProvider;
use App\ServiceProviders\LogServiceProvider;
use Jotup\Contracts\Application;

class Bootstrap implements \Jotup\Contracts\Bootstrap
{
    public function getServiceProviders(): array
    {
        return [
            AppServiceProvider::class,
            LogServiceProvider::class,
        ];
    }

    public function boot(Application $application): void
    {

    }

}