<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\DispatchTimeSlot;
use App\Models\OperationArea;
use App\Models\OperationCity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPlacementService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly BasketProductService $basketProductService,
        private readonly PayOnDeliveryService $payOnDeliveryService
    )
    {
    }

    public function placeCustomerCartOrder(User $customer, array $payload): Order
    {
        $cart = Cart::with(['items.sku.product.basketComponents.componentSku.stocks', 'items.sku.product.basketComponents.unit', 'coupon'])
            ->where('user_id', $customer->id)
            ->first();
        if (!$cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty.'],
            ]);
        }

        $city = OperationCity::query()->findOrFail($payload['city_id']);
        $area = OperationArea::query()->findOrFail($payload['area_id']);
        $slot = DispatchTimeSlot::query()->findOrFail($payload['dispatch_time_slot_id']);

        return DB::transaction(function () use ($customer, $payload, $cart, $city, $area, $slot) {
            $inventoryItems = $cart->items->flatMap(function ($item) {
                $product = $item->sku?->product;
                if ($this->basketProductService->isBasketProduct($product)) {
                    return $this->basketProductService->componentRequirements($product, (float) $item->quantity);
                }

                return [[
                    'sku' => $item->sku,
                    'qty' => (float) $item->quantity,
                ]];
            })->toArray();

            if (!$this->inventoryService->reserve($inventoryItems)) {
                throw ValidationException::withMessages([
                    'cart' => ['Insufficient stock for one or more items.'],
                ]);
            }

            $subtotal = (float) $cart->items->sum(fn ($item) => (float) $item->sku->price * (int) $item->quantity);
            $discount = (float) ($cart->coupon_discount ?? 0);
            $deliveryFee = (float) ($area->delivery_fee ?? 0);
            $tax = 0;
            $total = max(0, $subtotal - $discount) + $deliveryFee + $tax;

            if (($payload['payment_mode'] ?? null) === PayOnDeliveryService::METHOD) {
                $this->payOnDeliveryService->assertCartEligible($cart, $total, (int) $city->id);
            }

            $order = Order::create([
                'user_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'delivery_status' => 'pending_assignment',
                'city_id' => $city->id,
                'area_id' => $area->id,
                'dispatch_time_slot_id' => $slot->id,
                'city_name' => $city->name,
                'area_name' => $area->name,
                'dispatch_time_label' => $slot->label,
                'dispatch_start_time' => $slot->start_time,
                'dispatch_end_time' => $slot->end_time,
                'payment_mode' => $payload['payment_mode'],
                'payment_reference' => $payload['payment_reference'] ?? null,
                'order_note' => $payload['order_note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total' => $total,
                'placed_at' => now(),
                'delivery_snapshot' => [
                    'mode' => $payload['payment_mode'],
                    'city' => $city->name,
                    'area' => $area->name,
                    'dispatch_slot' => sprintf('%s (%s - %s)', $slot->label, $slot->start_time, $slot->end_time),
                ],
                'delivery_address_snapshot' => $payload['delivery_address_snapshot'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'sku_id' => $cartItem->sku_id,
                    'product_option_id' => $cartItem->sku_id,
                    'product_id' => $cartItem->sku?->product_id,
                    'quantity' => $cartItem->quantity,
                    'price_snapshot' => (float) $cartItem->sku->price,
                    'product_name_snapshot' => $cartItem->product_name_snapshot ?: $cartItem->sku?->product?->title,
                    'option_label_snapshot' => $cartItem->option_label_snapshot,
                    'image_snapshot' => $cartItem->image_snapshot ?: $cartItem->sku?->product?->image,
                    'weight_snapshot' => (float) ($cartItem->sku->weight ?? 0),
                    'length_snapshot' => (float) ($cartItem->sku->length ?? 0),
                    'width_snapshot' => (float) ($cartItem->sku->width ?? 0),
                    'height_snapshot' => (float) ($cartItem->sku->height ?? 0),
                ]);

                $product = $cartItem->sku?->product;
                if ($this->basketProductService->isBasketProduct($product)) {
                    $this->basketProductService->createOrderItemComponents($orderItem, $product, (float) $cartItem->quantity);
                }
            }

            $this->inventoryService->markBasketComponentsReserved($order->fresh('items.components'));

            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'method' => $payload['payment_mode'],
                'status' => 'pending',
                'gateway_response' => [
                    'provider' => $payload['payment_mode'],
                    'mode' => 'checkout',
                ],
            ]);

            $cart->items()->delete();
            $cart->update([
                'coupon_id' => null,
                'coupon_discount' => 0,
            ]);

            return $order;
        });
    }

    public function placeAdminOrderForCustomer(User $adminUser, User $customer, array $payload): Order
    {
        $city = OperationCity::query()->findOrFail($payload['city_id']);
        $area = OperationArea::query()->findOrFail($payload['area_id']);
        $slot = DispatchTimeSlot::query()->findOrFail($payload['dispatch_time_slot_id']);

        $skus = Sku::query()
            ->with(['stocks', 'product.basketComponents.componentSku.stocks', 'product.basketComponents.unit'])
            ->whereIn('id', collect($payload['items'])->pluck('sku_id')->unique()->values())
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($adminUser, $customer, $payload, $city, $area, $slot, $skus) {
            $inventoryItems = [];
            $subtotal = 0;

            foreach ($payload['items'] as $line) {
                $sku = $skus->get((int) $line['sku_id']);
                if (!$sku) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more selected SKUs are invalid.'],
                    ]);
                }

                $qty = (int) $line['quantity'];
                if ($this->basketProductService->isBasketProduct($sku->product)) {
                    array_push($inventoryItems, ...$this->basketProductService->componentRequirements($sku->product, (float) $qty));
                } else {
                    $inventoryItems[] = ['sku' => $sku, 'qty' => (float) $qty];
                }
                $subtotal += ((float) $sku->price * $qty);
            }

            if (!$this->inventoryService->reserve($inventoryItems)) {
                throw ValidationException::withMessages([
                    'items' => ['Insufficient stock for one or more selected products.'],
                ]);
            }

            $deliveryFee = (float) ($area->delivery_fee ?? 0);
            $tax = 0;
            $total = max(0, $subtotal) + $deliveryFee + $tax;

            $order = Order::create([
                'created_by_admin_id' => $adminUser->id,
                'user_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'delivery_status' => 'pending_assignment',
                'city_id' => $city->id,
                'area_id' => $area->id,
                'dispatch_time_slot_id' => $slot->id,
                'city_name' => $city->name,
                'area_name' => $area->name,
                'dispatch_time_label' => $slot->label,
                'dispatch_start_time' => $slot->start_time,
                'dispatch_end_time' => $slot->end_time,
                'payment_mode' => $payload['payment_mode'],
                'payment_reference' => $payload['payment_reference'] ?? null,
                'order_note' => $payload['order_note'] ?? null,
                'internal_note' => $payload['internal_note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total' => $total,
                'placed_at' => now(),
            ]);

            foreach ($payload['items'] as $line) {
                $sku = $skus->get((int) $line['sku_id']);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'sku_id' => $sku->id,
                    'product_option_id' => $sku->id,
                    'product_id' => $sku->product_id,
                    'quantity' => (int) $line['quantity'],
                    'price_snapshot' => (float) $sku->price,
                    'product_name_snapshot' => $sku->product?->title,
                    'option_label_snapshot' => $sku->display_label,
                    'image_snapshot' => $sku->image_path ?: $sku->product?->image,
                    'weight_snapshot' => (float) ($sku->weight ?? 0),
                    'length_snapshot' => (float) ($sku->length ?? 0),
                    'width_snapshot' => (float) ($sku->width ?? 0),
                    'height_snapshot' => (float) ($sku->height ?? 0),
                ]);

                if ($this->basketProductService->isBasketProduct($sku->product)) {
                    $this->basketProductService->createOrderItemComponents($orderItem, $sku->product, (float) $line['quantity']);
                }
            }

            $this->inventoryService->markBasketComponentsReserved($order->fresh('items.components'));

            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'method' => $payload['payment_mode'],
                'status' => 'pending',
                'gateway_response' => [
                    'provider' => $payload['payment_mode'],
                    'mode' => 'admin_create_order',
                ],
            ]);

            return $order;
        });
    }
}
