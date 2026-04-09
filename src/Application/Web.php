<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Container\Container;
use Jotup\ErrorHandler;
use Jotup\Log\Logger;
use Psr\Log\LoggerInterface;

class Web extends Base
{
    private Bootstrap $bootstrap;
    private Container $container;

    private $test = '123';

    public function __construct(Bootstrap $bootstrap)
    {
        defined('APP_DEBUG') or define('APP_DEBUG', false);

        $this->bootstrap = $bootstrap;
        $this->container = new Container();
        $this->registerBootstrapLogger();
        $this->registerErrorHandlers();
        $this->bootstrap->boot($this->container);
    }

    protected function registerBootstrapLogger(): void
    {
        $this->container->bind(
            LoggerInterface::class,
            Logger::class,
            values: ['routes' => [['class' => \Jotup\Log\Routes\Bootstrap::class]]]
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