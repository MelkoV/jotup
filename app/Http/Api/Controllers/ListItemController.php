<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Contracts\Services\ListServiceContract;
use App\Http\Api\Requests\ListItem\CompleteRequest;
use App\Http\Api\Requests\ListItem\CreateRequest;
use App\Http\Api\Requests\ListItem\DeleteRequest;
use App\Http\Api\Requests\ListItem\UpdateRequest;

final class ListItemController extends Controller
{
    public function __construct(
        private readonly ListServiceContract $listService,
    ) {
    }

    public function create(CreateRequest $request): \Jotup\Http\Response\Result\JsonResult
    {
        return $this->json(
            $this->listService->createListItem($request->toData())->toArray(),
            201,
        );
    }

    public function update(UpdateRequest $request): array|\Jotup\Http\Response\Result\JsonResult
    {
        try {
            return $this->listService->updateListItem($request->toData())->toArray();
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function delete(DeleteRequest $request): array|\Jotup\Http\Response\Result\JsonResult
    {
        try {
            return [
                'success' => $this->listService->deleteListItem($request->toData()),
            ];
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function complete(CompleteRequest $request): array|\Jotup\Http\Response\Result\JsonResult
    {
        try {
            return $this->listService->completeListItem($request->toData())->toArray();
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
