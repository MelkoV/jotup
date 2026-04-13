<?php

declare(strict_types=1);

namespace App\Data\List;

use App\Data\ListItem\ListItemData;
use Jotup\Data\Data;

final class ListViewData extends Data
{
    public function __construct(
        public readonly ListData $model,
        /** @var list<ListItemData> */
        public readonly array $items,
        /** @var list<ListMemberData> */
        public readonly array $members = [],
    ) {
    }
}
