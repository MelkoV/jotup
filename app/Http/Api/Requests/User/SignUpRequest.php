<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\User;

use App\Data\User\SignUpData;
use App\Enums\UserDevice;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class SignUpRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('{{%users}}', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:50', 'confirmed:repeat_password'],
            'repeat_password' => ['required', 'string', 'min:8', 'max:50'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'device' => ['required', Rule::enum(UserDevice::class)],
            'device_id' => ['required', 'string', 'max:100'],
        ];
    }

    public function toData(): SignUpData
    {
        return SignUpData::from($this->validated());
    }
}
