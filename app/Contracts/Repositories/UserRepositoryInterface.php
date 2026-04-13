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

    /**
     * @return array{user: UserData, password: string}
     */
    public function findAuthByEmail(string $email): array;

    /**
     * @return array{user: UserData, password: string}
     */
    public function findAuthById(string $id): array;

    public function updateAvatar(UserData $user, ?string $avatar): UserData;

    public function updateName(string $id, string $name): UserData;

    public function updatePassword(string $id, string $passwordHash): void;

    public function upsertDevice(UserData $data, UserDevice $device, ?string $deviceId = null): void;
}
