<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->when(isset($this->uuid), $this->uuid),
            'name' => $this->when(isset($this->name), $this->name),
            'sku' => $this->when(isset($this->sku), $this->sku),
            'description' => $this->when(isset($this->description), $this->description),
            'price' => $this->when(isset($this->price), number_format((float) $this->price, 2, '.', '')),
            'stock' => $this->when(isset($this->stock), $this->stock),
            'image' => $this->when(isset($this->image), $this->image),
            'image_url' => $this->when(isset($this->image), asset(Storage::url($this->image))),
            'status' => $this->when(isset($this->status), $this->status),
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
