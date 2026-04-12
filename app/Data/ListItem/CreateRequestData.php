<?php

declare(strict_types=1);

namespace App\Data\ListItem;

use App\Enums\ProductUnit;
use App\Enums\TodoPriority;
use Jotup\Data\Data;

final class CreateRequestData extends Data
{
    public function __construct(
        public readonly string $user_id,
        public readonly string $list_id,
        public readonly string $name,
        public readonly ?TodoPriority $priority = null,
        public readonly ?string $description = null,
        public readonly ?ProductUnit $unit = null,
        public readonly ?\DateTime $deadline = null,
        public readonly ?float $price = null,
        public readonly ?float $cost = null,
        public readonly ?float $count = null,
    ) {
    }
}
