<?php

declare(strict_types=1);

namespace Jotup\Application;

use Jotup\Config;
use Jotup\Contracts\WithCommands;
use Jotup\ErrorHandler;
use Jotup\Logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Symfony\Component\Console\Command\Command;

final class Console extends Base implements WithCommands
{
    /** @var Command[] */
    private array $commands = [];
    private ?\Jotup\Console\Routing\RouteCollection $routeCollection = null;

    public function run(): void
    {
        $application = new SymfonyConsoleApplication('Jotup Console', '1.0.0');
        $application->addCommands($this->commands);
        $application->run();
    }

    public function registerCommand(string $command): void
    {
        $this->container->bind($command, $command, true);
        $instance = $this->container->get($command);

        if (!$instance instanceof Command) {
            throw new \InvalidArgumentException(sprintf('Command "%s" must extend %s.', $command, Command::class));
        }

        $this->commands[] = $instance;
    }

    public function getRouteCollection(): \Jotup\Console\Routing\RouteCollection
    {
        return $this->routeCollection ??= new \Jotup\Console\Routing\RouteCollection();
    }

    protected function registerBootstrapLogger(): void
    {
        $this->container->bind(
            id: LoggerInterface::class,
            concrete: Logger::class,
            singleton: true,
            values: ['routes' => [
                ['class' => \Jotup\Logger\Routes\Bootstrap::class, 'exclude' => [LogLevel::DEBUG]],
                ['class' => \Jotup\Logger\Routes\Stream::class, 'config' => ['stream' => 'php://stderr'], 'exclude' => [LogLevel::DEBUG]],
            ]]
        );
    }

    protected function registerErrorHandlers(): void
    {
        $errorHandler = new ErrorHandler(
            fn (): LoggerInterface => $this->container->get(LoggerInterface::class),
            (bool) Config::get('error.ignoreVendorDeprecations', false),
        );
        $errorHandler->register(Config::get('error.level', E_ALL));
    }

    protected function bootApplication(): void
    {
        foreach ($this->bootstrap->routes() as $route) {
            $this->registerCommand($route);
        }
    }
}
