<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\User\SignInData;
use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\UserDevice;

interface UserServiceContract
{
    public function signUp(SignUpData $data): UserData;

    public function signIn(SignInData $data): UserData;

    public function attachDevice(UserData $user, UserDevice $device, string $deviceId): void;

    public function profile(string $userId): UserData;
}
