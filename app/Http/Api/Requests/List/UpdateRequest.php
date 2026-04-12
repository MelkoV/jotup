<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\UpdateRequestData;
use App\Http\Api\Requests\Rules\CheckCanEditList;
use Jotup\Http\Request\Request;

final class UpdateRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckCanEditList()],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:250'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function toData(): UpdateRequestData
    {
        return UpdateRequestData::from($this->validated());
    }
}
