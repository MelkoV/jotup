<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class ListMemberData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $avatar,
        public readonly int $item_count,
        public readonly float $sum,
    ) {
    }
}
