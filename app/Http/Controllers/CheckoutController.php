<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\ShippingRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manage checkout flows: shipping quote and placing orders.
 */
class CheckoutController extends Controller
{
    protected $inventoryService;
    protected $shippingService;

    public function __construct(InventoryService $inventoryService, ShippingRateService $shippingService)
    {
        $this->inventoryService = $inventoryService;
        $this->shippingService = $shippingService;
    }

    /**
     * Get or create cart for the current user/session.
     */
    protected function getCart(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        if ($user) {
            return Cart::where('user_id', $user->id)->first();
        } else {
            return Cart::where('session_id', $sessionId)->first();
        }
    }

    /**
     * Return shipping quotes based on destination and cart contents.
     */
    public function quoteShipping(Request $request)
    {
        $request->validate([
            'destination.zip' => 'required|string',
            'destination.state' => 'nullable|string',
            'destination.country' => 'required|string',
        ]);

        $cart = $this->getCart($request);

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
            ], 422);
        }

        // Prepare cart items with SKU details
        $items = $cart->items->map(function ($item) {
            return [
                'sku' => $item->sku,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Get shipping quotes
        $quotes = $this->shippingService->quote($request->destination, $items);

        return response()->json([
            'quotes' => $quotes,
        ]);
    }

    /**
     * Place an order (reserve inventory and create order).
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string|in:card,paypal,bank_transfer',
            'shipping_method' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        // Verify address belongs to user
        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $user, $address, $cart) {
                // Prepare items for inventory reservation
                $inventoryItems = $cart->items->map(function ($item) {
                    return [
                        'sku' => $item->sku,
                        'qty' => $item->quantity,
                    ];
                })->toArray();

                // Reserve inventory
                if (!$this->inventoryService->reserve($inventoryItems)) {
                    throw ValidationException::withMessages([
                        'cart' => ['Insufficient stock for one or more items'],
                    ]);
                }

                // Calculate totals
                $subtotal = $cart->items->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

                // Get shipping cost
                $destination = [
                    'country' => $address->country,
                    'state' => $address->state,
                    'zip' => $address->zip,
                ];

                $quotes = $this->shippingService->quote($destination, $inventoryItems);
                $shippingQuote = collect($quotes)->firstWhere('method', $request->shipping_method);

                if (!$shippingQuote) {
                    throw ValidationException::withMessages([
                        'shipping_method' => ['Invalid shipping method'],
                    ]);
                }

                $shippingCost = $shippingQuote['amount'];
                $total = $subtotal + $shippingCost;

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'shipping_address_id' => $address->id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'total' => $total,
                    'placed_at' => now(),
                ]);

                // Create order items
                foreach ($cart->items as $cartItem) {
                    $sku = $cartItem->sku;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'sku_id' => $sku->id,
                        'quantity' => $cartItem->quantity,
                        'price_snapshot' => $cartItem->price,
                        'weight_snapshot' => $sku->weight,
                        'length_snapshot' => $sku->length,
                        'width_snapshot' => $sku->width,
                        'height_snapshot' => $sku->height,
                    ]);
                }

                // Create payment record
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $total,
                    'method' => $request->payment_method,
                    'status' => 'pending',
                ]);

                // Clear cart
                $cart->items()->delete();

                return $order;
            });

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load(['items.sku.product', 'shippingAddress']),
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}