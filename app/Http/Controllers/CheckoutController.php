<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\PaymentGatewayManager;
use App\Services\ShippingRateService;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Manage checkout flows: shipping quote and placing orders.
 */
class CheckoutController extends Controller
{
    protected $inventoryService;
    protected $shippingService;
    protected $gatewayManager;

    public function __construct(
        InventoryService $inventoryService,
        ShippingRateService $shippingService,
        PaymentGatewayManager $gatewayManager
    )
    {
        $this->inventoryService = $inventoryService;
        $this->shippingService = $shippingService;
        $this->gatewayManager = $gatewayManager;
    }

    /**
     * Get or create cart for the current user/session.
     */
    protected function getCart(Request $request)
    {
        $user = $request->user();

        if ($user) {
            return Cart::where('user_id', $user->id)->first();
        } else {
            $sessionId = $request->hasSession() ? $request->session()->getId() : $request->header('X-Session-Id');
            return Cart::where('session_id', $sessionId)->first();
        }
    }

    /**
     * Return shipping quotes based on destination and cart contents.
     */
    public function quoteShipping(Request $request)
    {
        $request->validate([
            'address_id' => 'nullable|integer|exists:addresses,id',
            'destination.country' => 'nullable|string',
            'destination.state' => 'nullable|string',
            'destination.city' => 'nullable|string',
            'destination.area_or_district' => 'nullable|string',
            'destination.landmark' => 'nullable|string',
            'destination.postal_code' => 'nullable|string',
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

        $destination = $this->resolveDestination($request);
        $this->validateDestinationForDelivery($destination);

        $quotes = $this->shippingService->quote($destination, $items);

        if (empty($quotes)) {
            return response()->json([
                'message' => 'No delivery option is currently available for the selected address.',
                'quotes' => [],
            ], 422);
        }

        return response()->json([
            'quotes' => $quotes,
            'destination' => $destination,
        ]);
    }

    /**
     * Place an order (reserve inventory and create order).
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'nullable|string|in:card,bank_transfer,wallet',
            'payment_provider' => 'nullable|string|in:paystack,flutterwave',
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

        $cart = Cart::with('coupon')->where('user_id', $user->id)->first();

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

                $discount = (float) ($cart->coupon_discount ?? 0);

                // Get shipping cost
                $destination = $this->addressToDestination($address);
                $this->validateDestinationForDelivery($destination);

                $shippingItems = $cart->items->map(function ($item) {
                    return [
                        'sku' => $item->sku,
                        'quantity' => $item->quantity,
                    ];
                })->toArray();

                $quotes = $this->shippingService->quote($destination, $shippingItems);
                $shippingQuote = collect($quotes)->firstWhere('method', $request->shipping_method);

                if (!$shippingQuote) {
                    throw ValidationException::withMessages([
                        'shipping_method' => ['Selected delivery method is not available for this address.'],
                    ]);
                }

                $shippingCost = $shippingQuote['amount'];
                $tax = 0;
                $total = max(0, $subtotal - $discount) + $shippingCost + $tax;

                $deliveryAddressSnapshot = [
                    'full_name' => $address->full_name,
                    'phone' => $address->phone,
                    'email' => $address->email,
                    'country' => $address->country,
                    'state' => $address->state,
                    'city' => $address->city,
                    'area_or_district' => $address->area_or_district,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'landmark' => $address->landmark,
                    'postal_code' => $address->postal_code,
                    'delivery_note' => $address->delivery_note,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                ];

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'shipping_address_id' => $address->id,
                    'shipping_zone_id' => $shippingQuote['zone_id'] ?? null,
                    'shipping_method_id' => $shippingQuote['method_id'] ?? null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'delivery_status' => 'pending_assignment',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'delivery_fee' => $shippingCost,
                    'tax' => $tax,
                    'total' => $total,
                    'placed_at' => now(),
                    'delivery_snapshot' => [
                        'method' => $shippingQuote['method'] ?? null,
                        'method_name' => $shippingQuote['name'] ?? null,
                        'zone_name' => $shippingQuote['zone_name'] ?? null,
                        'zone_id' => $shippingQuote['zone_id'] ?? null,
                        'fee' => $shippingCost,
                        'cod_available' => $shippingQuote['cod_available'] ?? false,
                    ],
                    'delivery_address_snapshot' => $deliveryAddressSnapshot,
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

                $provider = $request->payment_provider;

                // Backward compatibility: if legacy payload uses payment_method as provider.
                if (in_array($request->payment_method, PaymentGatewayManager::SUPPORTED_PROVIDERS, true)) {
                    $provider = $request->payment_method;
                }

                $provider = $this->gatewayManager->resolveProviderForCheckout($provider);

                // Create payment record
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $total,
                    'method' => $request->payment_method ?: 'card',
                    'status' => 'pending',
                    'gateway_response' => [
                        'provider' => $provider,
                        'mode' => 'runtime',
                    ],
                ]);

                // Clear cart
                $cart->items()->delete();
                $cart->update([
                    'coupon_id' => null,
                    'coupon_discount' => 0,
                ]);

                return $order;
            });

            // Send order confirmation email
            Mail::to($user->email)->queue(new OrderConfirmation($order));

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load(['items.sku.product', 'shippingAddress']),
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function resolveDestination(Request $request): array
    {
        if ($request->filled('address_id')) {
            $address = Address::where('id', $request->integer('address_id'))
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            return $this->addressToDestination($address);
        }

        $destination = $request->input('destination', []);
        if (!isset($destination['zip']) && isset($destination['postal_code'])) {
            $destination['zip'] = $destination['postal_code'];
        }

        return $destination;
    }

    protected function addressToDestination(Address $address): array
    {
        return [
            'country' => $address->country,
            'state' => $address->state,
            'city' => $address->city,
            'area_or_district' => $address->area_or_district,
            'landmark' => $address->landmark,
            'postal_code' => $address->postal_code,
            'zip' => $address->postal_code ?: $address->zip,
        ];
    }

    protected function validateDestinationForDelivery(array $destination): void
    {
        if (blank($destination['country'] ?? null)) {
            throw ValidationException::withMessages([
                'destination.country' => ['Country is required.'],
            ]);
        }

        if (blank($destination['state'] ?? null) || blank($destination['city'] ?? null)) {
            throw ValidationException::withMessages([
                'destination.state' => ['State and city are required for delivery quotes.'],
            ]);
        }
    }
}