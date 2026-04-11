<?php

declare(strict_types=1);

namespace Jotup\Http\Response\Result;

class RedirectResult
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly string $location,
        public readonly int $status = 302,
        public readonly array $headers = []
    ) {
    }
}
