<?php

declare(strict_types=1);

namespace App\Data\List;

use Jotup\Data\Data;

final class PaginatedListsData extends Data
{
    /**
     * @param list<ListData> $data
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly array $data,
        public readonly array $meta,
    ) {
    }
}
