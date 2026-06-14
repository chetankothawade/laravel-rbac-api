<?php

namespace App\Http\Requests\Api\User;

use App\Enums\UserStatus;
use App\Http\Requests\Api\BaseApiRequest;

class UserStoreRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'email'     => 'required|email:rfc,dns|max:255|unique:users,email',
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:20',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
            'password'  => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
            'status'    => 'nullable|in:' . implode(',', UserStatus::creatableValues()),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower($this->email),
        ]);
    }
}
