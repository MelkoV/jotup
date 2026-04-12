<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\ListFilterData;
use App\Enums\ListFilterTemplate;
use App\Enums\ListType;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class FilteredListRequest extends Request
{
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(ListType::class)],
            'template' => ['nullable', Rule::enum(ListFilterTemplate::class)],
            'is_owner' => ['required', 'boolean'],
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'user_id' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_owner' => $this->input('is_owner', false),
            'page' => $this->input('page', 1),
            'per_page' => $this->input('per_page', 100),
            'user_id' => $this->userId(),
        ]);
    }

    public function toData(): ListFilterData
    {
        return ListFilterData::from($this->validated());
    }
}
