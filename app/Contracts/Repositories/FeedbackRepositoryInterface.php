<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Feedback\CreateFeedbackData;

interface FeedbackRepositoryInterface
{
    public function create(CreateFeedbackData $data): void;
}
