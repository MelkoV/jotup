<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\UserDevice;
use Jotup\Data\Data;

final class SignUpData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $name,
        public readonly UserDevice $device,
        public readonly string $device_id,
    ) {
    }
}
