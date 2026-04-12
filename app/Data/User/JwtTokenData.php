<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\JwtTokenType;
use Jotup\Data\Data;
use Ramsey\Uuid\Uuid;

final class JwtTokenData extends Data
{
    public function __construct(
        public readonly string $userId,
        public readonly JwtTokenType $type,
        public readonly int $time = 900,
        public readonly ?string $jti = null,
    ) {
    }

    public function toArray(): array
    {
        return [...parent::toArray(),
            'type' => $this->type->value,
            'iat' => time(),
            'exp' => time() + $this->time,
            'jti' => $this->jti ?? Uuid::uuid7()->toString(),
        ];
    }
}
