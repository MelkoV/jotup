<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\UserDevice;
use Jotup\Data\Data;

final class SignInData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly UserDevice $device,
        public readonly string $device_id,
    ) {
    }

    /**
     * @return array{email: string, password: string}
     */
    public function toAttemptArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
