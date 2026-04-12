<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\ListItem;

use App\Data\ListItem\CompleteRequestData;
use App\Enums\ProductUnit;
use App\Enums\TodoPriority;
use App\Http\Api\Requests\Rules\CheckCanEditListByItem;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class CompleteRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckCanEditListByItem()],
            'complete_user_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'version' => ['required', 'integer', 'min:1'],
            'priority' => ['nullable', Rule::enum(TodoPriority::class)],
            'description' => ['nullable', 'string', 'max:250'],
            'unit' => ['nullable', Rule::enum(ProductUnit::class)],
            'deadline' => ['nullable', 'date'],
            'price' => ['nullable', 'decimal:0,3'],
            'cost' => ['nullable', 'decimal:0,3'],
            'count' => ['nullable', 'decimal:0,3'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
            'complete_user_id' => $this->userId(),
        ]);
    }

    public function toData(): CompleteRequestData
    {
        return CompleteRequestData::from($this->validated());
    }
}
