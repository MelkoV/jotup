<?php

declare(strict_types=1);

namespace Jotup\ExecutionScope;

final readonly class ExecutionScope
{
    public function __construct(
        public ?string $userId = null,
        public ?string $requestId = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'requestId' => $this->requestId,
        ];
    }
}