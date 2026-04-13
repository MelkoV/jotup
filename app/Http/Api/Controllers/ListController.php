<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Contracts\Services\ListServiceContract;
use App\Data\List\PaginatedListsData;
use App\Data\List\ListViewData;
use App\Enums\DeleteListType;
use App\Http\Api\Requests\List\CopyRequest;
use App\Http\Api\Requests\List\CreateFromTemplateRequest;
use App\Http\Api\Requests\List\CreateRequest;
use App\Http\Api\Requests\List\DeleteRequest;
use App\Http\Api\Requests\List\DeleteTypesRequest;
use App\Http\Api\Requests\List\FilteredListRequest;
use App\Http\Api\Requests\List\JoinRequest;
use App\Http\Api\Requests\List\LeftRequest;
use App\Http\Api\Requests\List\ShareInfoRequest;
use App\Http\Api\Requests\List\ShareRequest;
use App\Http\Api\Requests\List\UpdateRequest;
use App\Http\Api\Requests\List\UpdateShareRequest;
use App\Http\Api\Requests\List\ViewRequest;

final class ListController extends Controller
{
    public function __construct(
        private readonly ListServiceContract $listService,
    ) {
    }

    public function index(FilteredListRequest $request): array
    {
        return PaginatedListsData::from($this->listService->getFilteredLists($request->toData()))->toArray();
    }

    public function create(CreateRequest $request): \Jotup\Http\Response\Result\JsonResult
    {
        return $this->json($this->listService->create($request->toData())->toArray(), 201);
    }

    public function view(ViewRequest $request): array
    {
        $data = $request->toData();

        return new ListViewData(
            model: $this->listService->findById($data->id, $data->user_id),
            items: $this->listService->getListItems($data->id),
            members: $this->listService->getListMembers($data->id),
        )->toArray();
    }

    public function update(UpdateRequest $request): array
    {
        return $this->listService->update($request->toData())->toArray();
    }

    public function copy(CopyRequest $request): array
    {
        $data = $request->toData();

        return $this->listService->copy($data->id, $data->user_id, $data->name)->toArray();
    }

    public function createFromTemplate(CreateFromTemplateRequest $request): array
    {
        $data = $request->toData();

        return $this->listService->createFromTemplate($data->id, $data->user_id, $data->name)->toArray();
    }

    public function share(ShareRequest $request): array
    {
        return $this->listService->getShareData($request->toData()->id)->toArray();
    }

    public function updateShare(UpdateShareRequest $request): array
    {
        return $this->listService->updateShareData($request->toData())->toArray();
    }

    public function join(JoinRequest $request): array
    {
        return $this->listService->joinByLink($request->toData())->toArray();
    }

    public function info(ShareInfoRequest $request): array
    {
        return $this->listService->findPublicInfoByShortUrl($request->toData()->url)->toArray();
    }

    public function left(LeftRequest $request): array
    {
        $data = $request->toData();
        $this->listService->leftUser($data->id, $data->user_id);

        return ['success' => true];
    }

    public function deleteTypes(DeleteTypesRequest $request): array
    {
        $data = $request->toData();
        $list = $this->listService->findById($data->id);

        return [
            DeleteListType::Left->value => true,
            DeleteListType::Delete->value => $list->owner_id === $data->user_id,
        ];
    }

    public function delete(DeleteRequest $request): array
    {
        $data = $request->toData();
        $this->listService->delete($data->id);

        return ['success' => true];
    }
}
