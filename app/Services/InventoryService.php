<?php

namespace App\Services;

use App\Models\Sku;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

/**
 * Handles SKU stock reservations and adjustments.
 */
class InventoryService
{
    /**
     * Reserve quantities for an order. Uses DB transactions to avoid race conditions.
     *
     * @param array<int,array{sku:Sku, qty:int}> $items
     * @return bool
     */
    public function reserve(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = $item['qty'];
                /** @var Stock|null $stock */
                $stock = $sku->stocks()->lockForUpdate()->first();
                if (!$stock || $stock->on_hand - $stock->reserved < $qty) {
                    return false; // insufficient stock
                }
                $stock->reserved += $qty;
                $stock->save();
                // TODO: log inventory movement (reserve)
            }
            return true;
        }, 3);
    }
}