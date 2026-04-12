<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\ListItem;

use App\Data\ListItem\CreateRequestData;
use App\Enums\ProductUnit;
use App\Enums\TodoPriority;
use App\Http\Api\Requests\Rules\CheckCanEditList;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class CreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'list_id' => ['required', 'uuid', 'bail', new CheckCanEditList()],
            'name' => ['required', 'string', 'min:1', 'max:100'],
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
        $this->merge(['user_id' => $this->userId()]);
    }

    public function toData(): CreateRequestData
    {
        return CreateRequestData::from($this->validated());
    }
}
