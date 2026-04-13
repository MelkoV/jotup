<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Contracts\Services\FeedbackServiceContract;
use App\Http\Api\Requests\Feedback\CreateRequest;
use Jotup\Http\Response\Result\JsonResult;

final class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackServiceContract $feedbackService,
    ) {
    }

    public function create(CreateRequest $request): JsonResult
    {
        $this->feedbackService->create($request->toData());

        return $this->json(['success' => true], 201);
    }
}
