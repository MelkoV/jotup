<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(int $id)
    {
        $user = new User();
    }
}