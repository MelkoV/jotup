<?php

declare(strict_types=1);

namespace Jotup\Http\Response\Result;

class EmptyResult
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly int $status = 204,
        public readonly array $headers = []
    ) {
    }
}
