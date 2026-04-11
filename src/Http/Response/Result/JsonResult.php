<?php

declare(strict_types=1);

namespace Jotup\Http\Response\Result;

class JsonResult
{
    /**
     * @param array<mixed> $data
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly array $data,
        public readonly int $status = 200,
        public readonly array $headers = []
    ) {
    }
}
