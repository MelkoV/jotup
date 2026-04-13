<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class ShareData extends Data
{
    public function __construct(
        public readonly string $short_url,
        public readonly bool $is_share_link,
        public readonly bool $can_edit,
    ) {
    }
}
