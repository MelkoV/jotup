<?php

declare(strict_types=1);

namespace Jotup\Log\Routes;

use Jotup\Log\LogData;
use Psr\Log\LogLevel;

abstract class Route
{
    public function __construct(
        /** @var LogLevel[] $levels */
        protected array     $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ],
        /** @var LogLevel[] $exclude */
        protected array     $exclude = [],
        protected ?\Closure $template = null,
        protected string    $dateFormat = 'Y-m-d H:i:s',
        protected ?\Closure  $contextFormat = null
    )
    {

    }

    public function init(): void
    {

    }

    public function push(LogData $data): void
    {
        if (in_array($data->level, $this->levels) && !in_array($data->level, $this->exclude)) {
            $this->write($data);
        }
    }

    protected function makeMessage(LogData $data): string
    {
        $template = $this->template ?? $this->defaultTemplate(...);
        $contextFormatter = $this->contextFormat ?? $this->defaultContextFormat(...);
        $message = strtr($template($data), [
            '%date' => date($this->dateFormat),
            '%level' => strtoupper($data->level),
            '%user' => '',
            '%request' => '',
            '%route' => '',
            '%message' => $data->message,
            '%context' => $contextFormatter($data->context),
        ]);
        return $message;
    }

    abstract public function write(LogData $data): void;

    protected function defaultTemplate(LogData $data): string
    {
        return '[%date][%level][%user][%request][%route] %message. %context';
    }

    protected function defaultContextFormat(array $context = []): string
    {
        return json_encode($context);
    }
}