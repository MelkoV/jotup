<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Jotup\Contracts\Application;
use Jotup\Provider\ServiceProvider;

class AppServiceProvider implements ServiceProvider
{
    public function register(Application $application): void
    {
        $application->getContainer()->bind(
            id: UserRepositoryInterface::class,
            concrete: UserRepository::class,
            singleton: true,
        );
    }
}
