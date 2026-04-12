<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\UserStatus;
use Jotup\Data\Data;

final class UserData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly UserStatus $status,
        public readonly string $id,
        public readonly ?string $avatar = null,
    ) {
    }
}
