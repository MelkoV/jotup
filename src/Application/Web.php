<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Container\Container;
use Jotup\ErrorHandler;
use Jotup\Log\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Web extends Base
{
    private Bootstrap $bootstrap;
    private Container $container;

    public function __construct(Bootstrap $bootstrap)
    {
        defined('APP_DEBUG') or define('APP_DEBUG', false);

        $this->bootstrap = $bootstrap;
        $this->container = new Container();
        $this->registerBootstrapLogger();
        $this->registerErrorHandlers();
        $this->bootstrap->boot($this->container);
        $this->container->get(LoggerInterface::class)->debug('Bootstrap completed');
    }

    protected function registerBootstrapLogger(): void
    {
        $this->container->bind(
            LoggerInterface::class,
            Logger::class,
            values: ['routes' => [
                ['class' => \Jotup\Log\Routes\Bootstrap::class, 'exclude' => [LogLevel::DEBUG]],
                ['class' => \Jotup\Log\Routes\Stream::class, 'config' => ['stream' => 'php://stderr']],
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