<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AvatarQueueContract;
use App\Data\User\AvatarSyncMessageData;
use App\Data\User\UserData;
use Jotup\Redis\RedisClient;

final readonly class RedisAvatarQueue implements AvatarQueueContract
{
    public function __construct(
        private RedisClient $redis,
        private string $queue,
    ) {
    }

    public function push(UserData $user): void
    {
        $payload = json_encode(
            ['user_id' => $user->id],
            JSON_THROW_ON_ERROR,
        );

        $this->redis->lPush($this->queue, $payload);
    }

    public function pop(int $timeout = 5): ?AvatarSyncMessageData
    {
        $payload = $this->redis->brPop($this->queue, $timeout);
        if ($payload === null) {
            return null;
        }

        /** @var array{user_id?: string} $decoded */
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        if (!isset($decoded['user_id']) || $decoded['user_id'] === '') {
            throw new \RuntimeException('Avatar queue message does not contain user_id.');
        }

        return new AvatarSyncMessageData((string) $decoded['user_id']);
    }
}
