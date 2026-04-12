<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Services\ListServiceContract;
use App\Data\List\CreateRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\UpdateRequestData;
use App\Data\ListItem\CompleteRequestData as CompleteItemRequestData;
use App\Data\ListItem\CreateRequestData as CreateItemRequestData;
use App\Data\ListItem\DeleteRequestData as DeleteItemRequestData;
use App\Data\ListItem\ListItemData;
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

    public function findById(string $id): ListData
    {
        return $this->listRepository->findById($id);
    }

    public function delete(string $id): void
    {
        $this->listRepository->delete($id);
    }

    public function leftUser(string $listId, string $userId): void
    {
        $this->listRepository->leftUser($listId, $userId);
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

    public function deleteListItem(DeleteItemRequestData $data): bool
    {
        return $this->listRepository->deleteListItem($data);
    }

    public function getListItems(string $listId): array
    {
        return $this->listRepository->getListItems($listId);
    }
}
