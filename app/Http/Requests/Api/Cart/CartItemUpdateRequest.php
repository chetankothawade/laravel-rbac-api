<?php

namespace App\Http\Requests\Api\Cart;

use App\Http\Requests\Api\BaseApiRequest;

class CartItemUpdateRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:999',
        ];
    }
}
