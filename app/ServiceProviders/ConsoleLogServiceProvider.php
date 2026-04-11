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

class ConsoleLogServiceProvider implements ServiceProvider
{

    public function register(Application $application): void
    {
        $container = $application->getContainer();

        $loggerBinding = $container->bind(
            id: LoggerInterface::class,
            concrete: Logger::class,
            singleton: true,
            values: Config::get('console_logger')
        );

        // Bind logger with alias by link
        $container->bind('logger', $loggerBinding);
    }
}
