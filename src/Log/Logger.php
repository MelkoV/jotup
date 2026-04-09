<?php

declare(strict_types=1);

namespace Jotup\Log;

use Jotup\Log\Routes\Route;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{

    /** @var Route[] $routes */
    private array $routes = [];

    public function __construct(array $routes)
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

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $data = new LogData(level: $level, message: $message, context: $context);
        foreach ($this->routes as $route) {
            $route->push($data);
        }
    }
}