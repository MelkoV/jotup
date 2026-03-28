<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\DI\Container;
use Jotup\ErrorHandler;
use Jotup\Log\Logger;
use Psr\Log\LoggerInterface;

class Web extends Base
{
    private Bootstrap $bootstrap;

    public function __construct(Bootstrap $bootstrap)
    {
        defined('JOTUP_DEBUG') or define('JOTUP_DEBUG', false);

        $this->bootstrap = $bootstrap;
        $this->registerBootstrapLogger();
        ErrorHandler::register(Config::get('error.level', E_ALL));
        $this->bootstrap->boot();
    }

    protected function registerBootstrapLogger(): void
    {
        Container::bind(LoggerInterface::class, Logger::class, values: ['routes' => [['class' => \Jotup\Log\Routes\Bootstrap::class]]]);
    }

}