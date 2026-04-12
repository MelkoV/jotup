<?php

declare(strict_types=1);

namespace App\Data\ListItem;

use Jotup\Data\Data;

final class ListItemData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $user_name,
        public readonly string $list_id,
        public readonly int $version,
        public readonly bool $is_completed,
        public readonly string $name,
        public readonly ListItemAttributesData $attributes,
        public readonly ?string $description,
        public readonly ?string $user_avatar,
        public readonly ?string $completed_user_name,
        public readonly ?string $completed_user_avatar,
    ) {
    }
}
