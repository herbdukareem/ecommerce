<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryLedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemComponent;
use App\Models\OrderItemInventoryAllocation;
use App\Models\Product;
use App\Models\Sku;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles SKU stock reservations, batch tracking, and auditable inventory movements.
 */
class InventoryService
{
    /**
     * Reserve quantities for an order. Uses DB transactions to avoid race conditions.
     *
     * @param array<int,array{sku:Sku, qty:int|float}> $items
     */
    public function reserve(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = (float) $item['qty'];

                $stocks = $sku->stocks()->lockForUpdate()->orderByDesc('on_hand')->get();
                $available = $stocks->sum(fn (Stock $stock) => max(0, $stock->on_hand - $stock->reserved));

                if ($available < $qty) {
                    return false;
                }

                $remaining = $qty;
                foreach ($stocks as $stock) {
                    $canReserve = max(0, $stock->on_hand - $stock->reserved);
                    if ($canReserve <= 0) {
                        continue;
                    }

                    $toReserve = min($remaining, $canReserve);
                    $stock->reserved += $toReserve;
                    $stock->save();

                    $remaining -= $toReserve;
                    if ($remaining <= 0) {
                        break;
                    }
                }
            }

            return true;
        }, 3);
    }

    /**
     * Release reserved quantities back to available stock.
     *
     * @param array<int,array{sku:Sku, qty:int|float}> $items
     */
    public function release(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = (float) $item['qty'];

                $stocks = $sku->stocks()->lockForUpdate()->orderByDesc('reserved')->get();
                $remaining = $qty;

                foreach ($stocks as $stock) {
                    if ($stock->reserved <= 0) {
                        continue;
                    }

                    $toRelease = min($remaining, $stock->reserved);
                    $stock->reserved -= $toRelease;
                    $stock->save();

                    $remaining -= $toRelease;
                    if ($remaining <= 0) {
                        break;
                    }
                }
            }
        }, 3);
    }

    /**
     * Commit reserved quantities to sold stock, then allocate sold units to inventory batches.
     *
     * @param array<int,array{sku:Sku, qty:int|float}> $items
     */
    public function commit(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = (float) $item['qty'];

                $stocks = $sku->stocks()->lockForUpdate()->orderByDesc('reserved')->get();
                $reserved = $stocks->sum('reserved');
                if ($reserved < $qty) {
                    return false;
                }

                $remaining = $qty;
                foreach ($stocks as $stock) {
                    if ($stock->reserved <= 0) {
                        continue;
                    }

                    $toCommit = min($remaining, $stock->reserved);
                    $stock->reserved -= $toCommit;
                    $stock->on_hand = max(0, $stock->on_hand - $toCommit);
                    $stock->save();

                    $remaining -= $toCommit;
                    if ($remaining <= 0) {
                        break;
                    }
                }
            }

            return true;
        }, 3);
    }

    public function commitOrder(Order $order, ?User $performedBy = null): bool
    {
        return DB::transaction(function () use ($order, $performedBy) {
            $order->loadMissing('items.sku.product', 'items.components.componentSku.product');

            if ($order->items->isEmpty()) {
                return true;
            }

            $alreadyCommitted = InventoryLedgerEntry::query()
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->where('movement_type', 'order_deduction')
                ->exists();

            if ($alreadyCommitted) {
                return true;
            }

            $items = $this->inventoryItemsForOrder($order);

            if (empty($items)) {
                $hasCommittedBasketComponents = $order->items
                    ->flatMap(fn (OrderItem $item) => $item->components)
                    ->contains(fn (OrderItemComponent $component) => (bool) $component->inventory_committed);

                return $hasCommittedBasketComponents;
            }

            if (!$this->commit($items)) {
                return false;
            }

            $this->commitOrderInventory($order, $performedBy);

            return true;
        }, 3);
    }

    public function releaseOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items.sku', 'items.components.componentSku');

            $items = $this->inventoryItemsForOrder($order, onlyReservedComponents: true);

            if (!empty($items)) {
                $this->release($items);
            }

            $order->items
                ->flatMap(fn (OrderItem $item) => $item->components)
                ->filter(fn (OrderItemComponent $component) => $component->inventory_reserved && !$component->inventory_released && !$component->inventory_committed)
                ->each(fn (OrderItemComponent $component) => $component->update([
                    'inventory_released' => true,
                    'released_at' => now(),
                ]));
        }, 3);
    }

    public function markBasketComponentsReserved(Order $order): void
    {
        $order->loadMissing('items.components');

        foreach ($order->items as $item) {
            foreach ($item->components as $component) {
                if (!$component->inventory_reserved) {
                    $component->update([
                        'inventory_reserved' => true,
                        'reserved_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int,array{sku:Sku, qty:float}>
     */
    protected function inventoryItemsForOrder(Order $order, bool $onlyReservedComponents = false): array
    {
        $components = $order->items
            ->flatMap(fn (OrderItem $item) => $item->components)
            ->filter(fn (OrderItemComponent $component) => $component->componentSku);

        if ($components->isNotEmpty()) {
            return $components
                ->filter(function (OrderItemComponent $component) use ($onlyReservedComponents) {
                    if ($component->inventory_committed || $component->inventory_released) {
                        return false;
                    }

                    return !$onlyReservedComponents || $component->inventory_reserved;
                })
                ->map(fn (OrderItemComponent $component) => [
                    'sku' => $component->componentSku,
                    'qty' => (float) ($component->base_quantity ?: $component->total_quantity),
                ])
                ->values()
                ->all();
        }

        return $order->items
            ->filter(fn ($item) => $item->sku)
                ->map(fn ($item) => [
                    'sku' => $item->sku,
                'qty' => (float) $item->quantity,
                ])
                ->values()
                ->all();
    }

    public function commitOrderInventory(Order $order, ?User $performedBy = null): void
    {
        DB::transaction(function () use ($order, $performedBy) {
            $order->loadMissing('items.sku.product', 'items.components.componentSku.product');

            foreach ($order->items as $item) {
                if ($item->components->isNotEmpty()) {
                    foreach ($item->components as $component) {
                        if ($component->inventory_committed) {
                            continue;
                        }

                        $this->allocateOrderItemFromBatches($item, $performedBy, $order, $component);
                        $component->update([
                            'inventory_committed' => true,
                            'committed_at' => now(),
                        ]);
                    }
                    continue;
                }

                $this->allocateOrderItemFromBatches($item, $performedBy, $order);
            }
        }, 3);
    }

    public function addStock(array $payload, User $performedBy): InventoryBatch
    {
        return DB::transaction(function () use ($payload, $performedBy) {
            $sku = Sku::query()->with('product')->findOrFail((int) $payload['sku_id']);
            $product = $sku->product;
            if (!$product) {
                throw ValidationException::withMessages(['sku_id' => ['Selected SKU has no product.']]);
            }

            $quantity = (int) $payload['quantity'];
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => ['Quantity must be positive.']]);
            }

            $batch = InventoryBatch::create([
                'product_id' => $product->id,
                'variant_id' => $sku->id,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'cost_price' => $payload['cost_price'],
                'selling_price' => $payload['selling_price'],
                'expiry_date' => $payload['expiry_date'] ?? null,
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'batch_reference' => $payload['batch_reference'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $performedBy->id,
            ]);

            $this->syncSkuAndProductPrice($sku, (float) $payload['selling_price'], (float) $payload['cost_price'], $performedBy);

            $warehouse = Warehouse::firstOrCreate(
                ['vendor_id' => $product->vendor_id, 'name' => 'Default Warehouse'],
                ['location_id' => null]
            );

            $stock = Stock::firstOrCreate(
                ['sku_id' => $sku->id, 'warehouse_id' => $warehouse->id],
                ['on_hand' => 0, 'reserved' => 0]
            );

            $stock->on_hand += $quantity;
            $stock->save();

            $this->recordLedger([
                'product_id' => $product->id,
                'variant_id' => $sku->id,
                'product_option_id' => $sku->id,
                'movement_type' => 'stock_addition',
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'balance_after' => (int) $stock->on_hand,
                'cost_price' => $batch->cost_price,
                'selling_price' => $batch->selling_price,
                'reference_type' => 'inventory_batch',
                'reference_id' => $batch->id,
                'note' => $batch->note,
                'performed_by' => $performedBy->id,
            ]);

            return $batch;
        }, 3);
    }

    public function updateBatchExpiry(InventoryBatch $batch, ?string $newExpiryDate, User $performedBy): InventoryBatch
    {
        return DB::transaction(function () use ($batch, $newExpiryDate, $performedBy) {
            $oldExpiry = optional($batch->expiry_date)->toDateString();
            $batch->update(['expiry_date' => $newExpiryDate]);

            $sku = Sku::query()->with('stocks')->find($batch->variant_id);
            $balance = $sku ? (int) $sku->stocks->sum('on_hand') : (int) $batch->quantity_remaining;

            $this->recordLedger([
                'product_id' => $batch->product_id,
                'variant_id' => $batch->variant_id,
                'product_option_id' => $batch->variant_id,
                'movement_type' => 'expiry_update',
                'quantity_in' => 0,
                'quantity_out' => 0,
                'balance_after' => $balance,
                'cost_price' => $batch->cost_price,
                'selling_price' => $batch->selling_price,
                'reference_type' => 'inventory_batch',
                'reference_id' => $batch->id,
                'note' => sprintf('Expiry updated from %s to %s', $oldExpiry ?: 'N/A', $newExpiryDate ?: 'N/A'),
                'performed_by' => $performedBy->id,
            ]);

            return $batch->fresh();
        }, 3);
    }

    protected function allocateOrderItemFromBatches(OrderItem $orderItem, ?User $performedBy, ?Order $order = null, ?OrderItemComponent $component = null): void
    {
        $sku = $component?->componentSku ?: $orderItem->sku;
        if (!$sku) {
            return;
        }

        $order = $order ?: $orderItem->order;

        $required = (float) ($component?->base_quantity ?: $component?->total_quantity ?: $orderItem->quantity);
        $batches = InventoryBatch::query()
            ->where('variant_id', $sku->id)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $allocatedCost = 0.0;
        $allocatedQty = 0;

        foreach ($batches as $batch) {
            if ($required <= 0) {
                break;
            }

            $take = min($required, (int) $batch->quantity_remaining);
            if ($take <= 0) {
                continue;
            }

            $batch->quantity_remaining -= $take;
            $batch->save();

            $lineCost = (float) $batch->cost_price * $take;
            $allocatedCost += $lineCost;
            $allocatedQty += $take;

            OrderItemInventoryAllocation::create([
                'order_item_id' => $orderItem->id,
                'order_item_component_id' => $component?->id,
                'inventory_batch_id' => $batch->id,
                'quantity' => $take,
                'unit_cost' => (float) $batch->cost_price,
                'total_cost' => $lineCost,
            ]);

            $balanceAfter = InventoryBatch::query()
                ->where('variant_id', $sku->id)
                ->sum('quantity_remaining');

            $this->recordLedger([
                'product_id' => $sku->product_id,
                'variant_id' => $sku->id,
                'product_option_id' => $sku->id,
                'movement_type' => 'order_deduction',
                'quantity_in' => 0,
                'quantity_out' => $take,
                'balance_after' => (int) $balanceAfter,
                'cost_price' => $batch->cost_price,
                'selling_price' => $orderItem->price_snapshot,
                'reference_type' => $component ? 'order_item_component' : 'order',
                'reference_id' => $component?->id ?: $order?->id,
                'note' => $component
                    ? 'Basket order item #' . $orderItem->id . ' component #' . $component->id . ' deducted from batch #' . $batch->id
                    : 'Order item #' . $orderItem->id . ' deducted from batch #' . $batch->id,
                'performed_by' => $performedBy?->id,
            ]);

            $required -= $take;
        }

        if ($required > 0) {
            $sku->loadMissing('stocks');
            $balanceAfter = (int) $sku->stocks->sum(fn (Stock $stock) => max(0, (int) $stock->on_hand - (int) $stock->reserved));

            $this->recordLedger([
                'product_id' => $sku->product_id,
                'variant_id' => $sku->id,
                'product_option_id' => $sku->id,
                'movement_type' => 'order_deduction',
                'quantity_in' => 0,
                'quantity_out' => $required,
                'balance_after' => $balanceAfter,
                'cost_price' => null,
                'selling_price' => $orderItem->price_snapshot,
                'reference_type' => $component ? 'order_item_component' : 'order',
                'reference_id' => $component?->id ?: $order?->id,
                'note' => $component
                    ? 'Basket order item #' . $orderItem->id . ' component #' . $component->id . ' deducted without batch allocation.'
                    : 'Order item #' . $orderItem->id . ' deducted without batch allocation.',
                'performed_by' => $performedBy?->id,
            ]);

            $allocatedQty += $required;
            $required = 0;
        }

        $unitCost = $allocatedQty > 0 ? ($allocatedCost / $allocatedQty) : null;
        $unitPrice = (float) $orderItem->price_snapshot;

        if ($component) {
            $component->update([
                'unit_cost_at_sale' => $unitCost,
                'total_cost_at_sale' => $allocatedCost,
            ]);

            $basketCost = (float) $orderItem->components()->sum('total_cost_at_sale');
            $orderItem->update([
                'unit_cost_at_sale' => $orderItem->quantity > 0 ? ($basketCost / (float) $orderItem->quantity) : null,
                'total_cost_at_sale' => $basketCost,
                'unit_price_at_sale' => $unitPrice,
                'total_price_at_sale' => $unitPrice * (float) $orderItem->quantity,
            ]);
            return;
        }

        $orderItem->update([
            'unit_cost_at_sale' => $unitCost,
            'total_cost_at_sale' => $allocatedCost,
            'unit_price_at_sale' => $unitPrice,
            'total_price_at_sale' => $unitPrice * (float) $orderItem->quantity,
        ]);
    }

    protected function recordLedger(array $payload): InventoryLedgerEntry
    {
        return InventoryLedgerEntry::create($payload);
    }

    protected function syncSkuAndProductPrice(Sku $sku, float $sellingPrice, float $costPrice, User $performedBy): void
    {
        $sku->update([
            'price' => $sellingPrice,
            'cost' => $costPrice,
            'cost_price' => $costPrice,
            'updated_by' => $performedBy->id,
        ]);

        $product = $sku->product()->with('skus')->first();
        if (!$product) {
            return;
        }

        $productPrice = (bool) $product->has_options
            ? (float) ($product->skus
                ->where('active', true)
                ->min('price') ?? $sellingPrice)
            : $sellingPrice;

        $product->update([
            'base_price' => $productPrice,
            'price' => $productPrice,
        ]);
    }
}
