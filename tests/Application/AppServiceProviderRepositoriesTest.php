<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AvatarUrlServiceContract;
use App\Contracts\Services\ListServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Repositories\ListRepository;
use App\Repositories\UserRepository;
use App\Services\GravatarAvatarUrlService;
use App\ServiceProviders\AppServiceProvider;
use App\Services\ListService;
use App\Services\UserService;
use Jotup\Container\Container;
use Jotup\Contracts\Application;
use Jotup\Contracts\RouteCollection;
use Jotup\Database\DatabaseManager;
use Jotup\Database\Db;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class AppServiceProviderRepositoriesTest extends TestCase
{
    public function testRepositoriesAndServicesAreBoundToTheirInterfaces(): void
    {
        $container = new Container();
        $connection = $this->createStub(ConnectionInterface::class);
        $container->bind(ConnectionInterface::class, $connection);
        $container->bind(DatabaseManager::class, new DatabaseManager($connection));
        $container->bind(Db::class, new Db($container->get(DatabaseManager::class)));

        $provider = new AppServiceProvider();
        $provider->register(new class($container) implements Application {
            public function __construct(
                private readonly Container $container,
            ) {
            }

            public function run(): void
            {
            }

            public function getContainer(): Container
            {
                return $this->container;
            }

            public function getRouteCollection(): RouteCollection
            {
                return new class implements RouteCollection {
                    public function all(): array
                    {
                        return [];
                    }
                };
            }
        });

        $this->assertInstanceOf(UserRepository::class, $container->get(UserRepositoryInterface::class));
        $this->assertInstanceOf(ListRepository::class, $container->get(ListRepositoryInterface::class));
        $this->assertInstanceOf(GravatarAvatarUrlService::class, $container->get(AvatarUrlServiceContract::class));
        $this->assertInstanceOf(UserService::class, $container->get(UserServiceContract::class));
        $this->assertInstanceOf(ListService::class, $container->get(ListServiceContract::class));
    }
}
