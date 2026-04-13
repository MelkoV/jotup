<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\UserDevice;
use App\Enums\UserStatus;
use App\Exceptions\UserNotFoundException;
use DateTime;
use Jotup\Database\Db;
use Ramsey\Uuid\Uuid;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Db $db,
    ) {
    }

    public function create(SignUpData $data): UserData
    {
        $id = Uuid::uuid7()->toString();
        $now = new DateTime()->format('Y-m-d H:i:s');
        $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new \RuntimeException('Unable to hash user password.');
        }

        $this->db->command()->insert('{{%users}}', [
            'id' => $id,
            'name' => $data->name,
            'email' => $data->email,
            'status' => UserStatus::Active->value,
            'avatar' => null,
            'password' => $passwordHash,
            'email_verified_at' => null,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        $user = $this->findById($id);
        $this->upsertDevice($user, $data->device, $data->device_id);

        return $user;
    }

    public function findById(string $id): UserData
    {
        $row = $this->db
            ->query()
            ->from('{{%users}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null) {
            throw new UserNotFoundException();
        }

        return $this->hydrateUser($row);
    }

    public function findByEmail(string $email): UserData
    {
        $row = $this->db
            ->query()
            ->from('{{%users}}')
            ->where(['email' => $email])
            ->one();

        if ($row === null) {
            throw new UserNotFoundException();
        }

        return $this->hydrateUser($row);
    }

    public function updateAvatar(UserData $user, ?string $avatar): UserData
    {
        $this->db->command()->update('{{%users}}', [
            'avatar' => $avatar,
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $user->id])->execute();

        return $this->findById($user->id);
    }

    public function upsertDevice(UserData $data, UserDevice $device, ?string $deviceId = null): void
    {
        $now = new DateTime()->format('Y-m-d H:i:s');
        $deviceId = $deviceId ?? '';
        $criteria = [
            'user_id' => $data->id,
            'device' => $device->value,
            'device_id' => $deviceId,
        ];

        $updated = $this->db->command()->update('{{%accounts}}', [
            'last_login_at' => $now,
            'updated_at' => $now,
        ], $criteria)->execute();

        if ($updated > 0) {
            return;
        }

        $this->db->command()->insert('{{%accounts}}', [
            'id' => Uuid::uuid7()->toString(),
            ...$criteria,
            'last_login_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateUser(array $row): UserData
    {
        return new UserData(
            email: (string) $row['email'],
            name: (string) $row['name'],
            status: UserStatus::from((string) $row['status']),
            id: (string) $row['id'],
            avatar: isset($row['avatar']) ? (string) $row['avatar'] : null,
        );
    }

}
