<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class ShortUrlRequestData extends Data
{
    public function __construct(
        public readonly string $url,
    ) {
    }
}
