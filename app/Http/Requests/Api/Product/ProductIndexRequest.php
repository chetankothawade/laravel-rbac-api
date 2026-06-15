<?php

namespace App\Http\Requests\Api\Product;

use App\Http\Requests\Api\BaseApiRequest;

class ProductIndexRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'sortedField' => 'nullable|in:id,name,sku,price,stock,created_at',
            'sortedBy' => 'nullable|in:asc,desc',
            'perPage' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'sortedField' => $this->input('sortedField', 'id'),
            'sortedBy' => $this->input('sortedBy', 'desc'),
            'perPage' => (int) $this->input('perPage', 10),
        ];
    }
}
