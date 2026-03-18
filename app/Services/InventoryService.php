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

                $stocks = $sku->stocks()->lockForUpdate()->orderByDesc('on_hand')->get();
                $available = $stocks->sum(fn (Stock $stock) => max(0, $stock->on_hand - $stock->reserved));

                if ($available < $qty) {
                    return false; // insufficient stock
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

    public function release(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = $item['qty'];

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

    public function commit(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $sku = $item['sku'];
                $qty = $item['qty'];

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
}