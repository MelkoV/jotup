<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\User;

use App\Data\User\UpdateProfileData;
use Jotup\Http\Request\Request;

final class UpdateProfileRequest extends Request
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['user_id' => $this->userId()]);
    }

    public function toData(): UpdateProfileData
    {
        return UpdateProfileData::from($this->validated());
    }
}
