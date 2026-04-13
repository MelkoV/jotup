<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\JoinRequestData;
use App\Http\Api\Requests\Rules\CheckListHasLinkAccess;
use Jotup\Http\Request\Request;

final class JoinRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckListHasLinkAccess()],
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

    public function toData(): JoinRequestData
    {
        return JoinRequestData::from($this->validated());
    }
}
