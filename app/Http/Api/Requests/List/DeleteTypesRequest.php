<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\RequestIdAndUserData;
use App\Http\Api\Requests\Rules\CheckCanViewList;
use Jotup\Http\Request\Request;

final class DeleteTypesRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckCanViewList()],
            'user_id' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
            'user_id' => $this->userId(),
        ]);
    }

    public function toData(): RequestIdAndUserData
    {
        return RequestIdAndUserData::from($this->validated());
    }
}
