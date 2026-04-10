<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Container\Container;
use Jotup\Contracts\Application;
use Jotup\Contracts\Bootstrap;
use Psr\Log\LoggerInterface;

abstract class Base implements Application
{
    protected Bootstrap $bootstrap;
    protected Container $container;

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
        $this->boot();
    }

    private function boot(): void
    {
        $this->registerServices();
        foreach ($this->bootstrap->getServiceProviders() as $providerClass) {
            new $providerClass()->register($this);
        }
        $this->bootstrap->boot($this);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    abstract protected function registerBootstrapLogger(): void;

    abstract protected function registerErrorHandlers(): void;

    abstract protected function registerServices(): void;
}