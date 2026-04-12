<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\JwtServiceContract;
use App\Contracts\Services\ListServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Repositories\ListRepository;
use App\Repositories\UserRepository;
use App\Services\JwtService;
use App\Services\ListService;
use App\Services\UserService;
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

        $application->getContainer()->bind(
            id: ListRepositoryInterface::class,
            concrete: ListRepository::class,
            singleton: true,
        );

        $application->getContainer()->bind(
            id: JwtServiceContract::class,
            concrete: JwtService::class,
            singleton: true,
        );

        $application->getContainer()->bind(
            id: UserServiceContract::class,
            concrete: UserService::class,
            singleton: true,
        );

        $application->getContainer()->bind(
            id: ListServiceContract::class,
            concrete: ListService::class,
            singleton: true,
        );
    }
}
