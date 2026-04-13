<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\List;

use App\Data\List\ShortUrlRequestData;
use Jotup\Http\Request\Request;

final class ShareInfoRequest extends Request
{
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['url' => $this->route('url')]);
    }

    public function toData(): ShortUrlRequestData
    {
        return ShortUrlRequestData::from($this->validated());
    }
}
