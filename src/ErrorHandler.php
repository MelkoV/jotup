<?php

declare(strict_types=1);

namespace Jotup;

use Jotup\DI\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class ErrorHandler
{
    public static function register(int $levels): void
    {
        set_error_handler([ErrorHandler::class, 'errorHandler'], $levels);
        set_exception_handler([ErrorHandler::class, 'exceptionHandler']);
    }


    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        Container::get(LoggerInterface::class)->log(self::getErrorLevel($errno), $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => self::getErrorTypeName($errno),
        ]);

        return true;
    }

    public static function exceptionHandler(Throwable $exception): void
    {
//        var_dump($exception);
        // @todo trace and previous
        $message = sprintf('Uncaught %s: %s', get_class($exception), $exception->getMessage());
        Container::get(LoggerInterface::class)->critical($message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'errno' => $exception->getCode(),
            'trace' => $exception->getTrace(),
        ]);
    }

    /**
     * Возвращает имя типа ошибки
     */
    private static function getErrorLevel(int $errno): string
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

    /**
     * Возвращает имя типа ошибки
     */
    private static function getErrorTypeName(int $errno): string
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