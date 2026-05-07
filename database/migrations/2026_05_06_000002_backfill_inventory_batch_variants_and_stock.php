<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $affectedSkuIds = [];

        DB::table('inventory_batches')
            ->whereNull('variant_id')
            ->orderBy('id')
            ->get(['id', 'product_id', 'selling_price'])
            ->each(function ($batch) use (&$affectedSkuIds) {
                $matches = DB::table('skus')
                    ->where('product_id', $batch->product_id)
                    ->where('active', true)
                    ->where('price', $batch->selling_price)
                    ->pluck('id');

                if ($matches->count() !== 1) {
                    return;
                }

                $skuId = (int) $matches->first();
                DB::table('inventory_batches')
                    ->where('id', $batch->id)
                    ->update([
                        'variant_id' => $skuId,
                        'updated_at' => now(),
                    ]);

                $affectedSkuIds[$skuId] = $skuId;
            });

        foreach (array_values($affectedSkuIds) as $skuId) {
            $this->syncStockForSku((int) $skuId);
        }
    }

    public function down(): void
    {
        // No rollback: this repairs legacy unassigned inventory data.
    }

    private function syncStockForSku(int $skuId): void
    {
        $sku = DB::table('skus')
            ->join('products', 'products.id', '=', 'skus.product_id')
            ->where('skus.id', $skuId)
            ->select('skus.id', 'products.vendor_id')
            ->first();

        if (!$sku) {
            return;
        }

        $batchRemaining = (int) DB::table('inventory_batches')
            ->where('variant_id', $skuId)
            ->sum('quantity_remaining');

        $stocks = DB::table('stocks')
            ->where('sku_id', $skuId)
            ->orderBy('id')
            ->get(['id', 'reserved']);

        if ($stocks->isEmpty()) {
            $warehouseId = DB::table('warehouses')
                ->where('vendor_id', $sku->vendor_id)
                ->where('name', 'Default Warehouse')
                ->value('id');

            if (!$warehouseId) {
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'vendor_id' => $sku->vendor_id,
                    'name' => 'Default Warehouse',
                    'location_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('stocks')->insert([
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'on_hand' => $batchRemaining,
                'reserved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $first = $stocks->first();
        foreach ($stocks as $stock) {
            DB::table('stocks')
                ->where('id', $stock->id)
                ->update([
                    'on_hand' => (int) $stock->reserved,
                    'updated_at' => now(),
                ]);
        }

        DB::table('stocks')
            ->where('id', $first->id)
            ->update([
                'on_hand' => (int) $first->reserved + $batchRemaining,
                'updated_at' => now(),
            ]);
    }
};
