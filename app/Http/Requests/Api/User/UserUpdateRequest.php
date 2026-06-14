<?php

namespace App\Http\Requests\Api\User;

use App\Enums\UserRole;
use App\Http\Requests\Api\BaseApiRequest;

class UserUpdateRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:20',
            'role'      => 'required|string|in:' . implode(',', UserRole::assignableValues()),
            'password'  => 'nullable|string|min:6|confirmed',
            'password_confirmation' => 'nullable|string',
        ];
    }
}
