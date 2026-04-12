<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\RequestIdData;
use App\Http\Api\Requests\Rules\CheckCanViewList;
use Jotup\Http\Request\Request;

final class ViewRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckCanViewList()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function toData(): RequestIdData
    {
        return RequestIdData::from($this->validated());
    }
}
