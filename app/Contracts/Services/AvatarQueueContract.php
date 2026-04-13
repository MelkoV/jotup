<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\User\AvatarSyncMessageData;
use App\Data\User\UserData;

interface AvatarQueueContract
{
    public function push(UserData $user): void;

    public function pop(int $timeout = 5): ?AvatarSyncMessageData;
}
