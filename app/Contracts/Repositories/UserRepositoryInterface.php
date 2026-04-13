<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\UserDevice;

interface UserRepositoryInterface
{
    public function create(SignUpData $data): UserData;

    public function findById(string $id): UserData;

    public function findByEmail(string $email): UserData;

    public function updateAvatar(UserData $user, ?string $avatar): UserData;

    public function upsertDevice(UserData $data, UserDevice $device, ?string $deviceId = null): void;
}
