<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class UpdateShareRequestData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly bool $is_share_link = false,
        public readonly bool $can_edit = false,
    ) {
    }
}
