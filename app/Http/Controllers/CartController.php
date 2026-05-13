<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Sku;
use App\Services\BasketProductService;
use Illuminate\Http\Request;

/**
 * Manage the shopping cart stored in the database.
 */
class CartController extends Controller
{
    public function __construct(private readonly BasketProductService $basketProductService)
    {
    }

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
            ->with(['sku.product.images', 'sku.product.basketComponents.componentSku.product', 'sku.product.basketComponents.componentSku.stocks', 'sku.product.basketComponents.unit', 'sku.stocks', 'productOption'])
            ->get()
            ->map(function ($item) {
                $sku = $item->sku;
                if (!$sku || !$sku->product) {
                    return null;
                }

                $isBasket = $this->basketProductService->isBasketProduct($sku->product);
                $availableStock = $isBasket
                    ? $this->basketProductService->availableQuantity($sku->product)
                    : $sku->stocks->sum(function ($stock) {
                        return $stock->on_hand - $stock->reserved;
                    });

                $unitPrice = (float) $sku->price;
                $optionLabel = $item->option_label_snapshot ?: ($item->productOption?->display_label ?? $sku->display_label ?? null);

                return [
                    'id' => $item->id,
                    'sku_id' => $sku->id,
                    'product_option_id' => $item->product_option_id,
                    'sku_code' => $sku->sku_code,
                    'product_id' => $sku->product->id,
                    'product_type' => $sku->product->product_type ?? Product::TYPE_SIMPLE,
                    'product_title' => $item->product_name_snapshot ?: $sku->product->title,
                    'product_slug' => $sku->product->slug,
                    'product_image' => $item->image_snapshot ?: $sku->product->image,
                    'product_images' => $sku->product->images()->orderBy('order')->get(['id', 'image_url', 'image_path', 'is_primary', 'order']),
                    'option_label' => $optionLabel,
                    'price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'subtotal' => $unitPrice * $item->quantity,
                    'available_stock' => $availableStock,
                    'in_stock' => $availableStock >= $item->quantity,
                    'basket_components' => $isBasket ? $sku->product->basketComponents->map(fn ($component) => [
                        'id' => $component->id,
                        'component_sku_id' => $component->component_sku_id,
                        'product_title' => $component->componentSku?->product?->title,
                        'sku_code' => $component->componentSku?->sku_code,
                        'quantity' => (float) $component->quantity,
                        'total_quantity' => (float) $component->quantity * (int) $item->quantity,
                        'unit_name' => $component->unit?->name ?: $component->componentSku?->unit,
                    ])->values() : [],
                ];
            })->filter()->values();

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
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'sku_id' => 'nullable|exists:skus,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        if (!$request->filled('product_id') && !$request->filled('sku_id')) {
            return response()->json([
                'message' => 'Either product_id or sku_id is required',
            ], 422);
        }

        $cart = $this->getCart($request);

        $product = null;
        $sku = null;

        if ($request->filled('product_id')) {
            $product = Product::with(['skus.stocks', 'basketComponents.componentSku.stocks', 'basketComponents.unit'])->findOrFail((int) $validated['product_id']);
        }

        if ($request->filled('sku_id')) {
            $sku = Sku::with(['product', 'stocks'])->findOrFail((int) $validated['sku_id']);
            $product = $product ?: $sku->product;
        }

        if (!$product) {
            return response()->json([
                'message' => 'Invalid product selected',
            ], 422);
        }

        if ($sku && (int) $sku->product_id !== (int) $product->id) {
            return response()->json([
                'message' => 'Selected option does not belong to selected product',
            ], 422);
        }

        if ($this->basketProductService->isBasketProduct($product)) {
            $sku = $this->basketProductService->parentSku($product);
            $sku?->loadMissing('stocks');
        } elseif (!$sku) {
            $sku = $product->skus()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
            $sku?->loadMissing('stocks');
        }

        if (!$sku) {
            return response()->json([
                'message' => 'No purchasable option is available for this product',
            ], 422);
        }

        if (!$this->basketProductService->isBasketProduct($product) && $product->has_options && !$request->filled('sku_id')) {
            return response()->json([
                'message' => 'Please select a product option before adding to cart',
            ], 422);
        }

        if (!(bool) $sku->active) {
            return response()->json([
                'message' => 'Selected option is inactive',
            ], 422);
        }

        // Check stock availability
        $availableStock = $this->basketProductService->isBasketProduct($product)
            ? $this->basketProductService->availableQuantity($product)
            : $sku->stocks->sum(function ($stock) {
                return $stock->on_hand - $stock->reserved;
            });

        if ($availableStock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available' => $availableStock,
            ], 422);
        }

        // Check if item already exists in cart
        $cartItem = $cart->items()->where('sku_id', $sku->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $validated['quantity'];

            if ($availableStock < $newQuantity) {
                return response()->json([
                    'message' => 'Cannot add more items. Insufficient stock.',
                    'available' => $availableStock,
                    'current_in_cart' => $cartItem->quantity,
                ], 422);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'price' => $sku->price,
                'product_name_snapshot' => $product->title,
                'option_label_snapshot' => $product->has_options && !$this->basketProductService->isBasketProduct($product) ? $sku->display_label : null,
                'image_snapshot' => $sku->image_path ?: $product->image,
            ]);
        } else {
            $cartItem = $cart->items()->create([
                'sku_id' => $sku->id,
                'product_option_id' => $product->has_options ? $sku->id : null,
                'quantity' => $validated['quantity'],
                'price' => $sku->price,
                'product_name_snapshot' => $product->title,
                'option_label_snapshot' => $product->has_options && !$this->basketProductService->isBasketProduct($product) ? $sku->display_label : null,
                'image_snapshot' => $sku->image_path ?: $product->image,
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
        $cartItem = $cart->items()->with('sku.stocks', 'sku.product.basketComponents.componentSku.stocks')->findOrFail($id);

        // Check stock availability
        $availableStock = $this->basketProductService->isBasketProduct($cartItem->sku?->product)
            ? $this->basketProductService->availableQuantity($cartItem->sku->product)
            : $cartItem->sku->stocks->sum(function ($stock) {
                return $stock->on_hand - $stock->reserved;
            });

        if ($availableStock < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available' => $availableStock,
            ], 422);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $cartItem->sku->price,
        ]);

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

        $cart = $this->getCart($request)->load('items.sku', 'coupon');
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

        $subtotal = (float) $cart->items->sum(fn ($item) => (float) $item->sku->price * (int) $item->quantity);
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
                    'product_option_id' => $guestItem->product_option_id,
                    'quantity' => $guestItem->quantity,
                    'price' => $guestItem->price,
                    'product_name_snapshot' => $guestItem->product_name_snapshot,
                    'option_label_snapshot' => $guestItem->option_label_snapshot,
                    'image_snapshot' => $guestItem->image_snapshot,
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
