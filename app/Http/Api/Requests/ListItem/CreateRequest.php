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
            'is_completed' => ['boolean'],
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
        $attributes = $this->input('attributes', []);
        $attributes = is_array($attributes) ? $attributes : [];

        $this->merge([
            'user_id' => $this->userId(),
            'is_completed' => $this->input('is_completed', $attributes['is_completed'] ?? false),
            'priority' => $this->input('priority', $attributes['priority'] ?? null),
            'description' => $this->input('description', $attributes['description'] ?? null),
            'unit' => $this->input('unit', $attributes['unit'] ?? null),
            'deadline' => $this->input('deadline', $attributes['deadline'] ?? null),
            'price' => $this->input('price', $attributes['price'] ?? null),
            'cost' => $this->input('cost', $attributes['cost'] ?? null),
            'count' => $this->input('count', $attributes['count'] ?? null),
        ]);
    }

    public function toData(): CreateRequestData
    {
        return CreateRequestData::from($this->validated());
    }
}
