<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\Feedback\CreateFeedbackData;

interface FeedbackServiceContract
{
    public function create(CreateFeedbackData $data): void;
}
