<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

class RequestIdAndUserData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $user_id,
    ) {
    }
}
