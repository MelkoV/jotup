<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Services\ListServiceContract;
use App\Data\List\CreateRequestData;
use App\Data\List\JoinRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\ListMemberData;
use App\Data\List\ListPublicInfoData;
use App\Data\List\ShareData;
use App\Data\List\UpdateRequestData;
use App\Data\List\UpdateShareRequestData;
use App\Data\ListItem\CompleteRequestData as CompleteItemRequestData;
use App\Data\ListItem\CreateRequestData as CreateItemRequestData;
use App\Data\ListItem\DeleteRequestData as DeleteItemRequestData;
use App\Data\ListItem\ListItemData;
use App\Data\ListItem\UncompleteRequestData as UncompleteItemRequestData;
use App\Data\ListItem\UpdateRequestData as UpdateItemRequestData;

final readonly class ListService implements ListServiceContract
{
    public function __construct(
        private ListRepositoryInterface $listRepository,
    ) {
    }

    public function create(CreateRequestData $data): ListData
    {
        return $this->listRepository->create($data);
    }

    public function update(UpdateRequestData $data): ListData
    {
        return $this->listRepository->update($data);
    }

    public function findById(string $id, ?string $currentUserId = null): ListData
    {
        return $this->listRepository->findById($id, $currentUserId);
    }

    public function delete(string $id): void
    {
        $this->listRepository->delete($id);
    }

    public function leftUser(string $listId, string $userId): void
    {
        $this->listRepository->leftUser($listId, $userId);
    }

    public function getShareData(string $id): ShareData
    {
        return $this->listRepository->getShareData($id);
    }

    public function updateShareData(UpdateShareRequestData $data): ShareData
    {
        return $this->listRepository->updateShareData($data);
    }

    public function joinByLink(JoinRequestData $data): ListData
    {
        return $this->listRepository->joinByLink($data);
    }

    public function findPublicInfoByShortUrl(string $shortUrl): ListPublicInfoData
    {
        return $this->listRepository->findPublicInfoByShortUrl($shortUrl);
    }

    public function copy(string $listId, string $userId, string $name): ListPublicInfoData
    {
        return $this->listRepository->copy($listId, $userId, $name);
    }

    public function createFromTemplate(string $listId, string $userId, string $name): ListPublicInfoData
    {
        return $this->listRepository->createFromTemplate($listId, $userId, $name);
    }

    public function getFilteredLists(ListFilterData $filter): array
    {
        return $this->listRepository->getFilteredLists($filter);
    }

    public function createListItem(CreateItemRequestData $data): ListItemData
    {
        return $this->listRepository->createListItem($data);
    }

    public function updateListItem(UpdateItemRequestData $data): ListItemData
    {
        return $this->listRepository->updateListItem($data);
    }

    public function completeListItem(CompleteItemRequestData $data): ListItemData
    {
        $this->updateListItem(UpdateItemRequestData::from($data));

        return $this->listRepository->completeListItem($data->id, $data->complete_user_id);
    }

    public function uncompleteListItem(UncompleteItemRequestData $data): ListItemData
    {
        $this->updateListItem(UpdateItemRequestData::from($data));

        return $this->listRepository->uncompleteListItem($data->id);
    }

    public function deleteListItem(DeleteItemRequestData $data): bool
    {
        return $this->listRepository->deleteListItem($data);
    }

    public function getListItems(string $listId): array
    {
        return $this->listRepository->getListItems($listId);
    }

    public function getListMembers(string $listId): array
    {
        return $this->listRepository->getListMembers($listId);
    }
}
