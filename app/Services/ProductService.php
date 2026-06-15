<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActiveInactiveStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getPaginatedProducts(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('status', ActiveInactiveStatus::ACTIVE->value);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy(
            $filters['sortedField'] ?? 'id',
            $filters['sortedBy'] ?? 'desc'
        );

        return $query->paginate($filters['perPage'] ?? 10);
    }

    public function getActiveByUuid(string $uuid): ?Product
    {
        return Product::where('uuid', $uuid)
            ->where('status', ActiveInactiveStatus::ACTIVE->value)
            ->first();
    }
}
