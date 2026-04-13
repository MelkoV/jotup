<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\UpdateShareRequestData;
use App\Http\Api\Requests\Rules\CheckIsListOwner;
use Jotup\Http\Request\Request;

final class UpdateShareRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckIsListOwner()],
            'is_share_link' => ['required', 'boolean'],
            'can_edit' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function toData(): UpdateShareRequestData
    {
        return UpdateShareRequestData::from($this->validated());
    }
}
