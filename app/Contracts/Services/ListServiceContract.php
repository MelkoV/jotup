<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\List\CreateRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\UpdateRequestData;
use App\Data\ListItem\CompleteRequestData as CompleteItemRequestData;
use App\Data\ListItem\CreateRequestData as CreateItemRequestData;
use App\Data\ListItem\DeleteRequestData as DeleteItemRequestData;
use App\Data\ListItem\ListItemData;
use App\Data\ListItem\UpdateRequestData as UpdateItemRequestData;

interface ListServiceContract
{
    public function create(CreateRequestData $data): ListData;

    public function update(UpdateRequestData $data): ListData;

    public function findById(string $id): ListData;

    public function delete(string $id): void;

    public function leftUser(string $listId, string $userId): void;

    /**
     * @return array{
     *     data: list<ListData>,
     *     meta: array<string, mixed>
     * }
     */
    public function getFilteredLists(ListFilterData $filter): array;

    public function createListItem(CreateItemRequestData $data): ListItemData;

    public function updateListItem(UpdateItemRequestData $data): ListItemData;

    public function completeListItem(CompleteItemRequestData $data): ListItemData;

    public function deleteListItem(DeleteItemRequestData $data): bool;

    /**
     * @return list<ListItemData>
     */
    public function getListItems(string $listId): array;
}
