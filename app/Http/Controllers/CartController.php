<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Sku;
use Illuminate\Http\Request;

/**
 * Manage the shopping cart stored in the database.
 */
class CartController extends Controller
{
    /**
     * Get or create cart for the current user/session.
     */
    protected function getCart(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null]
            );
        } else {
            $sessionId = $request->hasSession() ? $request->session()->getId() : $request->header('X-Session-Id');

            if (!$sessionId) {
                abort(422, 'Session id is required for guest carts');
            }

            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }

        return $cart;
    }

    /**
     * Show the current cart with items.
     */
    public function show(Request $request)
    {
        $cart = $this->getCart($request)->load(['coupon']);

        $items = $cart->items()
            ->with(['sku.product', 'sku.stocks'])
            ->get()
            ->map(function ($item) {
                $sku = $item->sku;
                $availableStock = $sku->stocks->sum(function ($stock) {
                    return $stock->on_hand - $stock->reserved;
                });

                return [
                    'id' => $item->id,
                    'sku_id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'product_id' => $sku->product->id,
                    'product_title' => $sku->product->title,
                    'product_slug' => $sku->product->slug,
                    'price' => $sku->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $sku->price * $item->quantity,
                    'available_stock' => $availableStock,
                    'in_stock' => $availableStock >= $item->quantity,
                ];
            });

        $subtotal = (float) $items->sum('subtotal');
        $discount = (float) ($cart->coupon_discount ?? 0);
        $total = max(0, $subtotal - $discount);

        return response()->json([
            'cart_id' => $cart->id,
            'items' => $items,
            'coupon' => $cart->coupon ? [
                'id' => $cart->coupon->id,
                'code' => $cart->coupon->code,
                'discount_type' => $cart->coupon->discount_type,
                'discount_value' => $cart->coupon->discount_value,
            ] : null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'item_count' => $items->sum('quantity'),
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'sku_id' => 'required|exists:skus,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = $this->getCart($request);
        $sku = Sku::with('stocks')->findOrFail($request->sku_id);

        // Check stock availability
        $availableStock = $sku->stocks->sum(function ($stock) {
            return $stock->on_hand - $stock->reserved;
        });

        if ($availableStock < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available' => $availableStock,
            ], 422);
        }

        // Check if item already exists in cart
        $cartItem = $cart->items()->where('sku_id', $sku->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($availableStock < $newQuantity) {
                return response()->json([
                    'message' => 'Cannot add more items. Insufficient stock.',
                    'available' => $availableStock,
                    'current_in_cart' => $cartItem->quantity,
                ], 422);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = $cart->items()->create([
                'sku_id' => $sku->id,
                'quantity' => $request->quantity,
                'price' => $sku->price,
            ]);
        }

        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem->load('sku.product'),
            'cart' => $this->show($request)->getData(true),
        ], 201);
    }

    /**
     * Update an existing item quantity.
     */
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = $this->getCart($request);
        $cartItem = $cart->items()->with('sku.stocks')->findOrFail($id);

        // Check stock availability
        $availableStock = $cartItem->sku->stocks->sum(function ($stock) {
            return $stock->on_hand - $stock->reserved;
        });

        if ($availableStock < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available' => $availableStock,
            ], 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem->fresh()->load('sku.product'),
            'cart' => $this->show($request)->getData(true),
        ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, $id)
    {
        $cart = $this->getCart($request);
        $cartItem = $cart->items()->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => $this->show($request)->getData(true),
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request)
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();
        $cart->update([
            'coupon_id' => null,
            'coupon_discount' => 0,
        ]);

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:64',
        ]);

        $cart = $this->getCart($request)->load('items', 'coupon');
        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cannot apply coupon to an empty cart',
            ], 422);
        }

        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($request->code)])->first();
        if (!$coupon) {
            return response()->json([
                'message' => 'Invalid coupon code',
            ], 422);
        }

        $subtotal = (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity);
        if (!$coupon->isUsable($subtotal)) {
            return response()->json([
                'message' => 'Coupon is not valid for this cart',
            ], 422);
        }

        $discount = $coupon->calculateDiscount($subtotal);
        $cart->update([
            'coupon_id' => $coupon->id,
            'coupon_discount' => $discount,
        ]);

        return response()->json([
            'message' => 'Coupon applied successfully',
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount' => $discount,
            ],
            'cart' => $this->show($request)->getData(true),
        ]);
    }

    public function removeCoupon(Request $request)
    {
        $cart = $this->getCart($request);
        $cart->update([
            'coupon_id' => null,
            'coupon_discount' => 0,
        ]);

        return response()->json([
            'message' => 'Coupon removed',
            'cart' => $this->show($request)->getData(true),
        ]);
    }

    /**
     * Merge guest cart with user cart after login.
     * For API-based apps using Sanctum, we accept guest_cart_id from the request.
     */
    public function merge(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // For API apps, accept guest_cart_id from request body
        $guestCartId = $request->input('guest_cart_id');

        if (!$guestCartId) {
            // No guest cart to merge, just return success
            return response()->json(['message' => 'No guest cart to merge']);
        }

        $guestCart = Cart::where('id', $guestCartId)
            ->whereNull('user_id')
            ->first();

        if (!$guestCart) {
            return response()->json(['message' => 'Guest cart not found or already merged']);
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Merge items
        foreach ($guestCart->items as $guestItem) {
            $existingItem = $userCart->items()
                ->where('sku_id', $guestItem->sku_id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $guestItem->quantity,
                ]);
            } else {
                $userCart->items()->create([
                    'sku_id' => $guestItem->sku_id,
                    'quantity' => $guestItem->quantity,
                    'price' => $guestItem->price,
                ]);
            }
        }

        // Delete guest cart
        $guestCart->delete();

        return response()->json([
            'message' => 'Cart merged successfully',
        ]);
    }
}