<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\User\UserData;

interface AvatarUrlServiceContract
{
    public function getAvatarUrl(UserData $user): ?string;
}
