<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceContract;
use App\Data\User\SignInData;
use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\UserDevice;
use App\Exceptions\UserNotFoundException;
use Jotup\Database\Db;

final readonly class UserService implements UserServiceContract
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private Db $db,
    ) {
    }

    public function signUp(SignUpData $data): UserData
    {
        // Repository already creates the initial device binding.
        return $this->userRepository->create($data);
    }

    public function signIn(SignInData $data): UserData
    {
        $row = $this->db
            ->query()
            ->from('{{%users}}')
            ->where(['email' => $data->email])
            ->one();

        if ($row === null || !$this->passwordMatches($data->password, (string) $row['password'])) {
            throw new UserNotFoundException();
        }

        $user = $this->userRepository->findById((string) $row['id']);
        $this->attachDevice($user, $data->device, $data->device_id);

        return $user;
    }

    public function attachDevice(UserData $user, UserDevice $device, string $deviceId): void
    {
        $this->userRepository->upsertDevice($user, $device, $deviceId);
    }

    public function profile(string $userId): UserData
    {
        return $this->userRepository->findById($userId);
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        $passwordInfo = password_get_info($storedPassword);

        if ($passwordInfo['algo'] !== null) {
            return password_verify($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
