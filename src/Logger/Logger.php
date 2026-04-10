<?php

declare(strict_types=1);

namespace Jotup\Logger;

use Jotup\Logger\Routes\Route;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    /** @var Route[] $routes */
    private array $routes = [];

    public function __construct(array $routes = [])
    {
        foreach ($routes as $config) {
            if (!isset($config['class'])) {
                continue;
            }
            $class = $config['class'];
            $routeConfig = $config['config'] ?? [];
            unset($config['class']);
            unset($config['config']);
            $route = new $class(...$config);
            $route->init(...$routeConfig);
            $this->routes[] = $route;
        }
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $data = new LogData(level: $level, message: $message, context: $context);
        foreach ($this->routes as $route) {
            $route->push($data);
        }
    }
}