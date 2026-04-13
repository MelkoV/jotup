<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Feedback;

use App\Data\Feedback\CreateFeedbackData;
use Jotup\Http\Request\Request;

final class CreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string'],
        ];
    }

    public function toData(): CreateFeedbackData
    {
        return CreateFeedbackData::from($this->validated());
    }
}
