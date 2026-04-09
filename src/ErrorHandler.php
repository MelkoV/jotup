<?php

declare(strict_types=1);

namespace Jotup;

use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

/**
 * @todo register_shutdown_function())
 */
class ErrorHandler
{
    private Closure $logger;

    public function __construct(Closure $logger)
    {
        $this->logger = $logger;
    }

    public function register(int $levels): void
    {
        set_error_handler([$this, 'errorHandler'], $levels);
        set_exception_handler([$this, 'exceptionHandler']);
    }


    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $this->getLogger()->log($this->getErrorLevel($errno), $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $this->getErrorName($errno),
        ]);

        return true;
    }

    public function exceptionHandler(Throwable $exception): void
    {
//        var_dump($exception);
        // @todo trace and previous
        $message = sprintf('Uncaught %s: %s', get_class($exception), $exception->getMessage());
        $this->getLogger()->critical($message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'errno' => $exception->getCode(),
            'trace' => $exception->getTrace(),
        ]);
    }

    private function getLogger(): LoggerInterface
    {
        return ($this->logger)();
    }

    private function getErrorLevel(int $errno): string
    {
        return match ($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE
            => LogLevel::CRITICAL,

            E_RECOVERABLE_ERROR
            => LogLevel::ERROR,

            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING
            => LogLevel::WARNING,

            E_NOTICE, E_USER_NOTICE
            => LogLevel::NOTICE,

            E_DEPRECATED, E_USER_DEPRECATED
            => LogLevel::INFO,

            default => LogLevel::DEBUG
        };
    }

    private function getErrorName(int $errno): string
    {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        return $types[$errno] ?? 'UNKNOWN';
    }
}