<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\ListItem;

use App\Data\ListItem\DeleteRequestData;
use App\Http\Api\Requests\Rules\CheckCanEditListByItem;
use App\Http\Api\Requests\Rules\CheckListItemIsNotCompleted;
use Jotup\Http\Request\Request;

final class DeleteRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'bail', new CheckCanEditListByItem(), new CheckListItemIsNotCompleted()],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function toData(): DeleteRequestData
    {
        return DeleteRequestData::from($this->validated());
    }
}
