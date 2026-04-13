<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AvatarQueueContract;
use App\Contracts\Services\UserServiceContract;
use App\Data\User\ChangePasswordData;
use App\Data\User\SignInData;
use App\Data\User\SignUpData;
use App\Data\User\UpdateProfileData;
use App\Data\User\UserData;
use App\Enums\UserDevice;
use App\Exceptions\UserNotFoundException;
use Jotup\Http\Exception\ValidationException;

final readonly class UserService implements UserServiceContract
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AvatarQueueContract $avatarQueue,
    ) {
    }

    public function signUp(SignUpData $data): UserData
    {
        $user = $this->userRepository->create($data);
        $this->dispatchAvatarSync($user);

        return $user;
    }

    public function signIn(SignInData $data): UserData
    {
        $auth = $this->userRepository->findAuthByEmail($data->email);

        if (!$this->passwordMatches($data->password, $auth['password'])) {
            throw new UserNotFoundException();
        }

        $user = $auth['user'];
        $this->attachDevice($user, $data->device, $data->device_id);
        $this->dispatchAvatarSync($user);

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

    public function updateProfile(UpdateProfileData $data): UserData
    {
        return $this->userRepository->updateName($data->user_id, $data->name);
    }

    public function changePassword(ChangePasswordData $data): void
    {
        $auth = $this->userRepository->findAuthById($data->user_id);

        if (!$this->passwordMatches($data->old_password, $auth['password'])) {
            throw new ValidationException([
                'old_password' => ['Current password is incorrect.'],
            ], 'Current password is incorrect.');
        }

        $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new \RuntimeException('Unable to hash user password.');
        }

        $this->userRepository->updatePassword($data->user_id, $passwordHash);
    }

    private function dispatchAvatarSync(UserData $user): void
    {
        $this->avatarQueue->push($user);
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
