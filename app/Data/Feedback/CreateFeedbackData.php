<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use Jotup\Data\Data;

final class CreateFeedbackData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $message,
    ) {
    }
}
