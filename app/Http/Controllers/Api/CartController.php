<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\CartItemStoreRequest;
use App\Http\Requests\Api\Cart\CartItemUpdateRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CartService $cartService,
        protected ProductService $productService
    ) {}

    public function show(): JsonResponse
    {
        return $this->success(
            'messages.cart_details_success',
            new CartResource($this->cartService->getActiveCart(request()->user()))
        );
    }

    public function store(CartItemStoreRequest $request): JsonResponse
    {
        $product = $this->productService->getActiveByUuid($request->validated('product_uuid'));

        if (! $product) {
            return $this->error('messages.product_not_found', [], 404);
        }

        return $this->safeExecute(
            'messages.cart_item_added',
            fn() => new CartResource($this->cartService->addItem(
                request()->user(),
                $product,
                (int) $request->validated('quantity')
            )),
            201
        );
    }

    public function update(CartItemUpdateRequest $request, string $itemUuid): JsonResponse
    {
        return $this->safeExecute(
            'messages.cart_item_updated',
            fn() => new CartResource($this->cartService->updateItem(
                request()->user(),
                $itemUuid,
                (int) $request->validated('quantity')
            ))
        );
    }

    public function destroy(string $itemUuid): JsonResponse
    {
        return $this->safeExecute(
            'messages.cart_item_removed',
            fn() => new CartResource($this->cartService->removeItem(request()->user(), $itemUuid))
        );
    }
}
