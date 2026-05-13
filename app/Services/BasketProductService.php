<?php

namespace App\Services;

use App\Models\BasketComponent;
use App\Models\OrderItem;
use App\Models\OrderItemComponent;
use App\Models\Product;
use App\Models\Sku;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BasketProductService
{
    public function isBasketProduct(?Product $product): bool
    {
        return $product?->product_type === Product::TYPE_BASKET;
    }

    public function parentSku(Product $basket): ?Sku
    {
        return $basket->skus()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function availableQuantity(Product $basket): float
    {
        $basket->loadMissing('basketComponents.componentSku.stocks');
        $components = $basket->basketComponents->where('is_required', true);

        if ($components->isEmpty()) {
            return 0.0;
        }

        $available = $components->map(function (BasketComponent $component) {
            $required = (float) $component->quantity;
            if ($required <= 0) {
                return 0.0;
            }

            $stock = $this->availableSkuStock($component->componentSku);

            return floor(($stock / $required) * 10000) / 10000;
        })->min();

        return max(0.0, (float) $available);
    }

    /**
     * @return array<int,array{sku:Sku, qty:float, component:?BasketComponent}>
     */
    public function componentRequirements(Product $basket, float $basketQuantity): array
    {
        $basket->loadMissing('basketComponents.componentSku.product', 'basketComponents.unit');
        if ($basket->basketComponents->isEmpty()) {
            throw ValidationException::withMessages([
                'basket' => ['Basket product has no components configured.'],
            ]);
        }

        return $basket->basketComponents
            ->filter(fn (BasketComponent $component) => (bool) $component->is_required)
            ->map(fn (BasketComponent $component) => [
                'sku' => $component->componentSku,
                'qty' => round((float) $component->quantity * $basketQuantity, 4),
                'component' => $component,
            ])
            ->values()
            ->all();
    }

    public function assertComponentsAvailable(Product $basket, float $basketQuantity): void
    {
        $requirements = $this->componentRequirements($basket, $basketQuantity);

        foreach ($requirements as $entry) {
            $available = $this->availableSkuStock($entry['sku']);
            if ($available + 0.0001 < (float) $entry['qty']) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient stock available for one or more basket components.'],
                ]);
            }
        }
    }

    public function estimatedComponentCost(Product $basket): float
    {
        $basket->loadMissing('basketComponents.componentSku');

        return (float) $basket->basketComponents->sum(function (BasketComponent $component) {
            $cost = (float) ($component->componentSku?->cost_price ?? $component->componentSku?->cost ?? 0);
            return $cost * (float) $component->quantity;
        });
    }

    public function syncComponents(Product $basket, array $components): void
    {
        if (!$basket->isBasket()) {
            $basket->basketComponents()->delete();
            return;
        }

        if (empty($components)) {
            throw ValidationException::withMessages([
                'basket_components' => ['At least one basket component is required.'],
            ]);
        }

        $basketVendorId = (int) $basket->vendor_id;
        $seen = [];

        DB::transaction(function () use ($basket, $components, $basketVendorId, &$seen) {
            $basket->basketComponents()->delete();

            foreach (array_values($components) as $index => $component) {
                $sku = Sku::query()->with('product')->findOrFail((int) ($component['component_sku_id'] ?? $component['sku_id'] ?? 0));
                if ((int) $sku->product?->vendor_id !== $basketVendorId) {
                    throw ValidationException::withMessages([
                        'basket_components' => ['Basket components must belong to the same vendor as the basket product.'],
                    ]);
                }

                if (in_array($sku->id, $seen, true)) {
                    throw ValidationException::withMessages([
                        'basket_components' => ['Duplicate basket components are not allowed.'],
                    ]);
                }

                $seen[] = $sku->id;
                $unitId = $component['unit_id'] ?? null;
                if ($unitId !== null) {
                    Unit::query()->findOrFail((int) $unitId);
                }

                $quantity = round((float) ($component['quantity'] ?? 0), 4);
                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'basket_components' => ['Basket component quantities must be greater than zero.'],
                    ]);
                }

                BasketComponent::create([
                    'basket_product_id' => $basket->id,
                    'component_sku_id' => $sku->id,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'sort_order' => (int) ($component['sort_order'] ?? $index),
                    'is_required' => array_key_exists('is_required', $component) ? (bool) $component['is_required'] : true,
                ]);
            }
        });
    }

    /**
     * @return Collection<int,OrderItemComponent>
     */
    public function createOrderItemComponents(OrderItem $orderItem, Product $basket, float $basketQuantity): Collection
    {
        $basket->loadMissing('basketComponents.componentSku.product', 'basketComponents.unit');

        return DB::transaction(function () use ($orderItem, $basket, $basketQuantity) {
            return $basket->basketComponents->map(function (BasketComponent $component) use ($orderItem, $basket, $basketQuantity) {
                $sku = $component->componentSku;
                $unit = $component->unit;
                $quantityPerBasket = round((float) $component->quantity, 4);
                $totalQuantity = round($quantityPerBasket * $basketQuantity, 4);
                $conversionFactor = (float) ($unit?->conversion_factor ?? 1);
                $baseQuantity = round($totalQuantity * $conversionFactor, 4);

                return OrderItemComponent::create([
                    'order_item_id' => $orderItem->id,
                    'basket_product_id' => $basket->id,
                    'component_sku_id' => $sku->id,
                    'unit_id' => $unit?->id,
                    'unit_name' => $unit?->name ?: $sku->unit,
                    'quantity_per_basket' => $quantityPerBasket,
                    'basket_quantity' => $basketQuantity,
                    'total_quantity' => $totalQuantity,
                    'conversion_factor' => $conversionFactor,
                    'base_quantity' => $baseQuantity,
                    'component_name_snapshot' => $sku->product?->title,
                    'component_sku_snapshot' => $sku->sku_code,
                ]);
            })->values();
        });
    }

    public function availableSkuStock(?Sku $sku): float
    {
        if (!$sku) {
            return 0.0;
        }

        $sku->loadMissing('stocks');

        return (float) $sku->stocks->sum(fn ($stock) => max(0, (float) $stock->on_hand - (float) $stock->reserved));
    }
}
