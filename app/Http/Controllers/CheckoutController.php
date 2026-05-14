<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\DispatchTimeSlot;
use App\Models\OperationArea;
use App\Models\OperationCity;
use App\Services\OrderPlacementService;
use App\Services\PayOnDeliveryService;
use App\Services\PaymentGatewayManager;
use App\Services\ShippingRateService;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Manage checkout flows: shipping quote and placing orders.
 */
class CheckoutController extends Controller
{
    protected $shippingService;
    protected $orderPlacementService;
    protected $gatewayManager;

    public function __construct(
        ShippingRateService $shippingService,
        OrderPlacementService $orderPlacementService,
        PaymentGatewayManager $gatewayManager,
        protected PayOnDeliveryService $payOnDeliveryService
    )
    {
        $this->shippingService = $shippingService;
        $this->orderPlacementService = $orderPlacementService;
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

    public function operationalCities()
    {
        $cities = OperationCity::query()
            ->active()
            ->orderByRaw('COALESCE(sort_order, 9999) asc')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'cities' => $cities,
        ]);
    }

    public function operationalAreas(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|integer|exists:operation_cities,id',
        ]);

        $areas = OperationArea::query()
            ->active()
            ->where('city_id', $validated['city_id'])
            ->orderByRaw('COALESCE(sort_order, 9999) asc')
            ->orderBy('name')
            ->get(['id', 'city_id', 'name', 'delivery_fee']);

        return response()->json([
            'areas' => $areas,
        ]);
    }

    public function dispatchTimeSlots()
    {
        $slots = DispatchTimeSlot::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get(['id', 'label', 'start_time', 'end_time', 'description'])
            ->map(function (DispatchTimeSlot $slot) {
                return [
                    'id' => $slot->id,
                    'label' => $slot->label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'display_time' => date('g:i A', strtotime((string) $slot->start_time)) . ' - ' . date('g:i A', strtotime((string) $slot->end_time)),
                    'description' => $slot->description,
                ];
            })
            ->values();

        return response()->json([
            'dispatch_time_slots' => $slots,
        ]);
    }

    public function paymentOptions(Request $request)
    {
        $request->validate([
            'city_id' => 'nullable|integer|exists:operation_cities,id',
            'area_id' => 'nullable|integer|exists:operation_areas,id',
        ]);

        $cart = $this->getCart($request)?->load(['items.sku.product']);
        $deliveryFee = 0;
        if ($request->filled('area_id')) {
            $deliveryFee = (float) (OperationArea::query()->where('id', $request->integer('area_id'))->value('delivery_fee') ?? 0);
        }

        $subtotal = $cart
            ? (float) $cart->items->sum(fn ($item) => (float) $item->sku->price * (int) $item->quantity)
            : 0;
        $discount = (float) ($cart?->coupon_discount ?? 0);
        $total = max(0, $subtotal - $discount) + $deliveryFee;

        $onlineGateways = collect($this->gatewayManager->checkoutList())
            ->map(fn ($gateway) => array_merge($gateway, ['available' => true, 'unavailable_reason' => null]))
            ->values()
            ->all();

        return response()->json([
            'payment_options' => array_merge($onlineGateways, [
                $this->payOnDeliveryService->optionForCart($cart, $total, $request->integer('city_id') ?: null),
            ]),
        ]);
    }

    /**
     * Place an order with simplified checkout payload.
     */
    public function placeOrder(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        $payload = $this->resolveCheckoutPayload($request, $user->id);

        try {
            $order = $this->orderPlacementService->placeCustomerCartOrder($user, $payload);

            // Send order confirmation email
            Mail::to($user->email)->queue(new OrderConfirmation($order));

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load(['items.sku.product', 'city', 'area', 'dispatchTimeSlot']),
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function resolveCheckoutPayload(Request $request, int $userId): array
    {
        if ($request->filled('address_id')) {
            $validated = $request->validate([
                'address_id' => 'required|integer|exists:addresses,id',
                'payment_method' => 'nullable|string',
                'payment_provider' => ['required', 'string', Rule::in(array_merge(PaymentGatewayManager::SUPPORTED_PROVIDERS, [PayOnDeliveryService::METHOD]))],
                'order_note' => 'nullable|string|max:1000',
                'payment_reference' => 'nullable|string|max:120',
            ]);

            $address = Address::query()
                ->where('id', (int) $validated['address_id'])
                ->where('user_id', $userId)
                ->firstOrFail();

            $cityName = trim((string) ($address->city_name ?: $address->city ?: 'Unknown City'));
            $areaName = trim((string) ($address->area_or_district ?: 'General Area'));

            $city = OperationCity::firstOrCreate(
                ['code' => Str::slug($cityName) ?: 'unknown-city'],
                ['name' => $cityName, 'status' => 'active']
            );

            $area = OperationArea::firstOrCreate(
                ['city_id' => $city->id, 'name' => $areaName],
                ['status' => 'active', 'delivery_fee' => 0]
            );

            $slot = DispatchTimeSlot::query()->active()->orderBy('sort_order')->orderBy('start_time')->first();
            if (!$slot) {
                $slot = DispatchTimeSlot::create([
                    'label' => 'Default dispatch',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'status' => 'active',
                    'sort_order' => 0,
                ]);
            }

            $provider = (string) $validated['payment_provider'];
            if ($provider !== PayOnDeliveryService::METHOD) {
                try {
                    $this->gatewayManager->resolveProviderForCheckout($provider);
                } catch (\Throwable $exception) {
                    throw ValidationException::withMessages([
                        'payment_provider' => ['Selected payment provider is not available.'],
                    ]);
                }
            }

            return [
                'city_id' => $city->id,
                'area_id' => $area->id,
                'dispatch_time_slot_id' => $slot->id,
                'payment_mode' => (string) ($provider ?? 'card'),
                'payment_reference' => $validated['payment_reference'] ?? null,
                'order_note' => $validated['order_note'] ?? null,
                'delivery_address_snapshot' => [
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
                ],
            ];
        }

        $validated = $request->validate([
            'city_id' => 'required|integer|exists:operation_cities,id',
            'area_id' => 'required|integer|exists:operation_areas,id',
            'dispatch_time_slot_id' => 'required|integer|exists:dispatch_time_slots,id',
            'payment_mode' => ['required', 'string', Rule::in(array_merge(PaymentGatewayManager::SUPPORTED_PROVIDERS, [PayOnDeliveryService::METHOD]))],
            'payment_reference' => 'nullable|string|max:120',
            'order_note' => 'nullable|string|max:1000',
        ]);

        $city = OperationCity::query()->find($validated['city_id']);
        $area = OperationArea::query()->find($validated['area_id']);
        $slot = DispatchTimeSlot::query()->find($validated['dispatch_time_slot_id']);

        if (!$city || $city->status !== 'active') {
            throw ValidationException::withMessages(['city_id' => ['Selected city must be active.']]);
        }

        if (!$area || $area->status !== 'active' || (int) $area->city_id !== (int) $city->id) {
            throw ValidationException::withMessages(['area_id' => ['Selected area must be active and belong to selected city.']]);
        }

        if (!$slot || $slot->status !== 'active') {
            throw ValidationException::withMessages(['dispatch_time_slot_id' => ['Selected dispatch slot must be active.']]);
        }

        if ($validated['payment_mode'] !== PayOnDeliveryService::METHOD) {
            try {
                $this->gatewayManager->resolveProviderForCheckout((string) $validated['payment_mode']);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'payment_mode' => ['Selected payment provider is not available.'],
                ]);
            }
        }

        return $validated;
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
