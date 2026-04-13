<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AvatarQueueContract;
use App\Contracts\Services\AvatarUrlServiceContract;
use App\Contracts\Services\FeedbackServiceContract;
use App\Contracts\Services\JwtServiceContract;
use App\Contracts\Services\ListServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Repositories\FeedbackRepository;
use App\Services\GravatarAvatarUrlService;
use App\Services\RedisAvatarQueue;
use App\Repositories\ListRepository;
use App\Repositories\UserRepository;
use App\Services\FeedbackService;
use App\Services\JwtService;
use App\Services\ListService;
use App\Services\UserService;
use Jotup\Config;
use Jotup\Contracts\Application;
use Jotup\Provider\ServiceProvider;
use Jotup\Redis\RedisClient;

class AppServiceProvider implements ServiceProvider
{
    public function register(Application $application): void
    {
        $container = $application->getContainer();

        $container->bind(
            id: RedisClient::class,
            concrete: new RedisClient(
                host: (string) Config::get('redis.host', '127.0.0.1'),
                port: (int) Config::get('redis.port', 6379),
                password: Config::get('redis.password'),
                database: (int) Config::get('redis.database', 0),
                timeout: (float) Config::get('redis.timeout', 2.0),
                readTimeout: (float) Config::get('redis.read_timeout', 5.0),
            ),
            singleton: true,
        );

        $container->bind(
            id: AvatarQueueContract::class,
            concrete: new RedisAvatarQueue(
                redis: $container->get(RedisClient::class),
                queue: (string) Config::get('redis.queues.avatar', 'avatar:sync'),
            ),
            singleton: true,
        );

        $container->bind(
            id: FeedbackRepositoryInterface::class,
            concrete: FeedbackRepository::class,
            singleton: true,
        );

        $container->bind(
            id: UserRepositoryInterface::class,
            concrete: UserRepository::class,
            singleton: true,
        );

        $container->bind(
            id: ListRepositoryInterface::class,
            concrete: ListRepository::class,
            singleton: true,
        );

        $container->bind(
            id: JwtServiceContract::class,
            concrete: JwtService::class,
            singleton: true,
        );

        $container->bind(
            id: AvatarUrlServiceContract::class,
            concrete: GravatarAvatarUrlService::class,
            singleton: true,
        );

        $container->bind(
            id: FeedbackServiceContract::class,
            concrete: FeedbackService::class,
            singleton: true,
        );

        $container->bind(
            id: UserServiceContract::class,
            concrete: UserService::class,
            singleton: true,
        );

        $container->bind(
            id: ListServiceContract::class,
            concrete: ListService::class,
            singleton: true,
        );
    }
}
