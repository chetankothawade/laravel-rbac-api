<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');
        $itemCount = $this->items->sum('quantity');
        $subtotal = $this->items->sum(fn($item) => (int) $item->quantity * (float) $item->unit_price);

        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'items' => CartItemResource::collection($items),
            'totals' => [
                'items_count' => (int) $itemCount,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($subtotal, 2, '.', ''),
            ],
            'created_at' => $this->when(
                isset($this->created_at),
                optional($this->created_at)->format('d/m/Y h:i A')
            ),
            'updated_at' => $this->when(
                isset($this->updated_at),
                optional($this->updated_at)->format('d/m/Y h:i A')
            ),
        ];
    }
}
