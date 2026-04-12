<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

class RequestIdData extends Data
{
    public function __construct(
        public readonly string $id
    ) {
    }
}
