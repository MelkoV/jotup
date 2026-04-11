<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use Jotup\Database\Db;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Db $db,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        return $this->db
            ->query()
            ->from('{{%users}}')
            ->where(['id' => $id])
            ->one();
    }
}
