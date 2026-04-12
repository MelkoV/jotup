<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\CreateRequestData;
use App\Enums\ListType;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class CreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'type' => ['required', Rule::enum(ListType::class)],
            'is_template' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:250'],
            'owner_id' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['owner_id' => $this->userId()]);
    }

    public function toData(): CreateRequestData
    {
        return CreateRequestData::from($this->validated());
    }
}
