<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quantity = (int) $this->quantity;
        $unitPrice = (float) $this->unit_price;

        return [
            'uuid' => $this->uuid,
            'quantity' => $quantity,
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'line_total' => number_format($quantity * $unitPrice, 2, '.', ''),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
