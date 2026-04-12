<?php

declare(strict_types=1);

namespace App\Data\List;

use App\Enums\ListType;
use Jotup\Data\Data;

final class CreateRequestData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $owner_id,
        public readonly bool $is_template,
        public readonly ListType $type,
        public readonly ?string $description = null,
    ) {
    }
}
