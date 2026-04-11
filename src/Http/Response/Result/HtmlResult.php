<?php

declare(strict_types=1);

namespace Jotup\Http\Response\Result;

class HtmlResult
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly string $content,
        public readonly int $status = 200,
        public readonly array $headers = []
    ) {
    }
}
