<?php

declare(strict_types=1);

namespace Jotup\Http\Exception;

use RuntimeException;

class HttpException extends RuntimeException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly array $headers = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
