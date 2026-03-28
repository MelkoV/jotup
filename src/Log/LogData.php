<?php

declare(strict_types=1);

namespace Jotup\Log;

readonly class LogData
{
    public function __construct(
        public string $level,
        public string $message,
        public array $context = []
    )
    {

    }
}