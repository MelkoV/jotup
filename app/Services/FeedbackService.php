<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Contracts\Services\FeedbackServiceContract;
use App\Data\Feedback\CreateFeedbackData;

final readonly class FeedbackService implements FeedbackServiceContract
{
    public function __construct(
        private FeedbackRepositoryInterface $feedbackRepository,
    ) {
    }

    public function create(CreateFeedbackData $data): void
    {
        $this->feedbackRepository->create($data);
    }
}
