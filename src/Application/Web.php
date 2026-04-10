<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Container\Container;
use Jotup\ErrorHandler;
use Jotup\Logger\Logger;
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
        $this->container = new Container(debug: false);
        $this->registerBootstrapLogger();
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $this->container->setLogger($logger);
        $this->registerErrorHandlers();
        $this->bootstrap->boot($this->container);
        $this->container->get(LoggerInterface::class)->debug('Bootstrap completed');
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