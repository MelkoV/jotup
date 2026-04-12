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
use Psr\Log\NullLogger;

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

        if (defined('APP_TESTING') && APP_TESTING) {
            $nullLogger = new NullLogger();
            $container->bind(self::INNER_LOGGER_ID, $nullLogger);
            $container->bind(LoggerInterface::class, $nullLogger);
            $container->bind('logger', $nullLogger);

            return;
        }

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
