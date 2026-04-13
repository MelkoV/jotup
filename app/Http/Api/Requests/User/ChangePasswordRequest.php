<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\User;

use App\Data\User\ChangePasswordData;
use Jotup\Http\Request\Request;

final class ChangePasswordRequest extends Request
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'old_password' => ['required', 'string', 'min:8', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:50', 'confirmed:repeat_password'],
            'repeat_password' => ['required', 'string', 'min:8', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['user_id' => $this->userId()]);
    }

    public function toData(): ChangePasswordData
    {
        return ChangePasswordData::from($this->validated());
    }
}
