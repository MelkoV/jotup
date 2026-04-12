<?php

declare(strict_types=1);

namespace App\Data\List;

use App\Enums\ListType;
use Jotup\Data\Data;

final class ListData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $owner_id,
        public readonly string $name,
        public readonly bool $is_template,
        public readonly ListType $type,
        public readonly string $owner_name,
        public readonly \DateTime $touched_at,
        public readonly bool $can_edit,
        public readonly ?string $owner_avatar = null,
        public readonly ?string $description = null,
    ) {
    }
}
