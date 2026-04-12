<?php

declare(strict_types=1);

namespace App\Data\List;

use App\Enums\ListFilterTemplate;
use App\Enums\ListType;
use Jotup\Data\Data;

final class ListFilterData extends Data
{
    public function __construct(
        public readonly string $user_id,
        public readonly int $page = 1,
        public readonly int $per_page = 100,
        public readonly bool $is_owner = false,
        public readonly ?ListType $type = null,
        public readonly ?ListFilterTemplate $template = null,
        public readonly ?string $text = null,
    ) {
    }
}
