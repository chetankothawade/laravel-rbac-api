<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(ProductIndexRequest $request): JsonResponse
    {
        $products = $this->productService->getPaginatedProducts($request->filters());

        return $this->paginate(
            'messages.product_list_success',
            ProductResource::collection($products),
            $products
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $product = $this->productService->getActiveByUuid($uuid);

        if (! $product) {
            return $this->error('messages.product_not_found', [], 404);
        }

        return $this->success('messages.product_details_success', new ProductResource($product));
    }
}
