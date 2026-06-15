<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CartService
{
    public function getActiveCart(User $user): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => $user->id,
            'status' => 'active',
        ])->load('items.product');
    }

    public function addItem(User $user, Product $product, int $quantity): Cart
    {
        $this->ensureProductHasStock($product, $quantity);

        return DB::transaction(function () use ($user, $product, $quantity) {
            $cart = Cart::firstOrCreate([
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            $newQuantity = $quantity + (int) ($item?->quantity ?? 0);
            $this->ensureProductHasStock($product, $newQuantity);

            if ($item) {
                $item->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $product->price,
                ]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);
            }

            return $cart->fresh()->load('items.product');
        });
    }

    public function updateItem(User $user, string $itemUuid, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $itemUuid, $quantity) {
            $cart = $this->getActiveCartForUpdate($user);
            $item = $this->findCartItemOrFail($cart, $itemUuid);
            $product = $item->product;

            $this->ensureProductHasStock($product, $quantity);

            $item->update([
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ]);

            return $cart->fresh()->load('items.product');
        });
    }

    public function removeItem(User $user, string $itemUuid): Cart
    {
        return DB::transaction(function () use ($user, $itemUuid) {
            $cart = $this->getActiveCartForUpdate($user);
            $item = $this->findCartItemOrFail($cart, $itemUuid);

            $item->delete();

            return $cart->fresh()->load('items.product');
        });
    }

    private function getActiveCartForUpdate(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (! $cart) {
            throw new HttpException(404, __('messages.cart_not_found'));
        }

        return $cart;
    }

    private function findCartItemOrFail(Cart $cart, string $itemUuid): CartItem
    {
        $item = $cart->items()
            ->with('product')
            ->where('uuid', $itemUuid)
            ->first();

        if (! $item) {
            throw new HttpException(404, __('messages.cart_item_not_found'));
        }

        return $item;
    }

    private function ensureProductHasStock(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            throw new HttpException(422, __('messages.product_out_of_stock'));
        }
    }
}
