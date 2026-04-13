<?php

declare(strict_types=1);

namespace App\Data\User;

use Jotup\Data\Data;

final class UpdateProfileData extends Data
{
    public function __construct(
        public readonly string $user_id,
        public readonly string $name,
    ) {
    }
}
