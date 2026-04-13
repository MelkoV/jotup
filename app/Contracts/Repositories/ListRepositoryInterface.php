<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\List\CreateRequestData;
use App\Data\List\JoinRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\ListMemberData;
use App\Data\List\ListPublicInfoData;
use App\Data\List\ShareData;
use App\Data\List\UpdateRequestData;
use App\Data\List\UpdateShareRequestData;
use App\Data\ListItem\CreateRequestData as CreateItemRequestData;
use App\Data\ListItem\DeleteRequestData as DeleteItemRequestData;
use App\Data\ListItem\ListItemData;
use App\Data\ListItem\UncompleteRequestData as UncompleteItemRequestData;
use App\Data\ListItem\UpdateRequestData as UpdateItemRequestData;

interface ListRepositoryInterface
{
    public function create(CreateRequestData $data): ListData;

    public function update(UpdateRequestData $data): ListData;

    public function findById(string $id, ?string $currentUserId = null): ListData;

    public function delete(string $id): void;

    public function leftUser(string $listId, string $userId): void;

    public function addUser(string $listId, string $userId): void;

    public function touch(string $id): void;

    public function getShareData(string $id): ShareData;

    public function updateShareData(UpdateShareRequestData $data): ShareData;

    public function joinByLink(JoinRequestData $data): ListData;

    public function findPublicInfoByShortUrl(string $shortUrl): ListPublicInfoData;

    public function copy(string $listId, string $userId, string $name): ListPublicInfoData;

    public function createFromTemplate(string $listId, string $userId, string $name): ListPublicInfoData;

    /**
     * @return array{
     *     data: list<ListData>,
     *     meta: array<string, mixed>
     * }
     */
    public function getFilteredLists(ListFilterData $filter): array;

    public function createListItem(CreateItemRequestData $data): ListItemData;

    public function updateListItem(UpdateItemRequestData $data): ListItemData;

    public function completeListItem(string $listItemId, string $completeUserId): ListItemData;

    public function uncompleteListItem(string $listItemId): ListItemData;

    public function deleteListItem(DeleteItemRequestData $data): bool;

    /**
     * @return list<ListItemData>
     */
    public function getListItems(string $listId): array;

    /**
     * @return list<ListMemberData>
     */
    public function getListMembers(string $listId): array;
}
