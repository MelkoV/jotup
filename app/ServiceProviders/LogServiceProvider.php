<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use Jotup\Config;
use Jotup\Contracts\Application;
use Jotup\ExecutionScope\ExecutionScopeProvider;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Jotup\Provider\ServiceProvider;
use Psr\Log\LoggerInterface;

class LogServiceProvider implements ServiceProvider
{

    public function register(Application $application): void
    {
        $application->getContainer()->bind(
            id: ExecutionScopeProviderInterface::class,
            concrete: ExecutionScopeProvider::class,
            singleton: true
        );

        $loggerBinding = $application->getContainer()->bind(
            id: LoggerInterface::class,
            singleton: true,
            values: Config::get('logger')
        );

        // Bind logger with alias by link
        $application->getContainer()->bind('logger', $loggerBinding);
    }
}