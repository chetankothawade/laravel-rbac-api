<?php

namespace App\Http\Requests\Api\Cart;

use App\Http\Requests\Api\BaseApiRequest;

class CartItemStoreRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'product_uuid' => 'required|uuid|exists:products,uuid',
            'quantity' => 'required|integer|min:1|max:999',
        ];
    }
}
