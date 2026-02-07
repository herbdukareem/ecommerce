<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $sessionId = $request->session()->getId();

        if ($user) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => $sessionId]
            );
        } else {
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
        $cart = $this->getCart($request);

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

        $total = $items->sum('subtotal');

        return response()->json([
            'cart_id' => $cart->id,
            'items' => $items,
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
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request)
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    /**
     * Merge guest cart with user cart after login.
     */
    public function merge(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $sessionId = $request->session()->getId();
        $guestCart = Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if (!$guestCart) {
            return response()->json(['message' => 'No guest cart to merge']);
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