<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Container\Container;
use Jotup\Contracts\Bootstrap;
use Jotup\ErrorHandler;
use Jotup\Logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Web extends Base
{
    protected function registerServices(): void
    {

    }

    protected function registerBootstrapLogger(): void
    {
        $this->container->bind(
            id: LoggerInterface::class,
            concrete: Logger::class,
            singleton: true,
            values: ['routes' => [
                ['class' => \Jotup\Logger\Routes\Bootstrap::class, 'exclude' => [LogLevel::DEBUG]],
                ['class' => \Jotup\Logger\Routes\Stream::class, 'config' => ['stream' => 'php://stderr']],
            ]]
        );
    }

    protected function registerErrorHandlers(): void
    {
        $errorHandler = new ErrorHandler(function (): LoggerInterface {
            return $this->container->get(LoggerInterface::class);
        });
        $errorHandler->register(Config::get('error.level', E_ALL));
    }

}