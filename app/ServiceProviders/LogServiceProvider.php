<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use Jotup\Config;
use Jotup\Contracts\Application;
use Jotup\ExecutionScope\ExecutionScopeLogger;
use Jotup\ExecutionScope\ExecutionScopeProvider;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Jotup\Logger\Logger;
use Jotup\Provider\ServiceProvider;
use Psr\Log\LoggerInterface;

class LogServiceProvider implements ServiceProvider
{
    private const INNER_LOGGER_ID = 'logger.inner';

    public function register(Application $application): void
    {
        $container = $application->getContainer();

        $container->bind(
            id: ExecutionScopeProviderInterface::class,
            concrete: ExecutionScopeProvider::class,
            singleton: true
        );

        $container->bind(
            id: self::INNER_LOGGER_ID,
            concrete: Logger::class,
            singleton: true,
            values: Config::get('logger')
        );

        $loggerBinding = $container->bind(
            id: LoggerInterface::class,
            concrete: ExecutionScopeLogger::class,
            singleton: true,
            values: [
                'logger' => $container->get(self::INNER_LOGGER_ID),
                'scopeProvider' => $container->get(ExecutionScopeProviderInterface::class),
            ]
        );

        // Bind logger with alias by link
        $container->bind('logger', $loggerBinding);
    }
}
