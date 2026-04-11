<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

interface UserRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array;
}
