<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\User;

use App\Data\User\SignInData;
use App\Enums\UserDevice;
use App\Enums\UserStatus;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\Rule;

final class SignInRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::exists('{{%users}}', 'email')->where('status', UserStatus::Active->value)],
            'password' => ['required', 'string', 'min:8', 'max:50'],
            'device' => ['required', Rule::enum(UserDevice::class)],
            'device_id' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'auth.failed',
        ];
    }

    public function toData(): SignInData
    {
        return SignInData::from($this->validated());
    }
}
