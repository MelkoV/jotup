<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Data\Feedback\CreateFeedbackData;
use Jotup\Database\Db;
use Ramsey\Uuid\Uuid;

final readonly class FeedbackRepository implements FeedbackRepositoryInterface
{
    public function __construct(
        private Db $db,
    ) {
    }

    public function create(CreateFeedbackData $data): void
    {
        $this->db->command()->insert('{{%feedback}}', [
            'id' => Uuid::uuid7()->toString(),
            'name' => $data->name,
            'email' => $data->email,
            'message' => $data->message,
        ])->execute();
    }
}
