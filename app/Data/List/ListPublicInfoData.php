<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class ListPublicInfoData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $owner_name,
        public readonly ?string $owner_avatar,
    ) {
    }
}
