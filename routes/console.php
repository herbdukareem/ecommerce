<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:backfill-batches {--dry-run : Report missing batches without writing changes}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $rows = DB::table('skus')
        ->join('products', 'products.id', '=', 'skus.product_id')
        ->leftJoin(DB::raw('(select sku_id, sum(on_hand) as on_hand from stocks group by sku_id) stock_totals'), 'stock_totals.sku_id', '=', 'skus.id')
        ->leftJoin(DB::raw('(select variant_id, sum(quantity_remaining) as batch_remaining from inventory_batches group by variant_id) batch_totals'), 'batch_totals.variant_id', '=', 'skus.id')
        ->select([
            'skus.id as sku_id',
            'skus.sku_code',
            'skus.price',
            'skus.cost',
            'skus.cost_price',
            'products.id as product_id',
            'products.title as product_title',
        ])
        ->selectRaw('COALESCE(stock_totals.on_hand, 0) as on_hand')
        ->selectRaw('COALESCE(batch_totals.batch_remaining, 0) as batch_remaining')
        ->whereRaw('COALESCE(stock_totals.on_hand, 0) > COALESCE(batch_totals.batch_remaining, 0)')
        ->orderBy('products.title')
        ->orderBy('skus.id')
        ->get();

    if ($rows->isEmpty()) {
        $this->info('All SKU stock is already backed by inventory batches.');
        return self::SUCCESS;
    }

    $this->table(
        ['SKU ID', 'SKU', 'Product', 'On Hand', 'Batch Remaining', 'Opening Qty'],
        $rows->map(fn ($row) => [
            $row->sku_id,
            $row->sku_code,
            $row->product_title,
            (int) $row->on_hand,
            (int) $row->batch_remaining,
            (int) $row->on_hand - (int) $row->batch_remaining,
        ])->all()
    );

    if ($dryRun) {
        $this->warn('Dry run only. Re-run without --dry-run to create opening inventory batches.');
        return self::SUCCESS;
    }

    DB::transaction(function () use ($rows) {
        foreach ($rows as $row) {
            $quantity = (int) $row->on_hand - (int) $row->batch_remaining;
            if ($quantity <= 0) {
                continue;
            }

            $costPrice = (float) ($row->cost_price ?? $row->cost ?? 0);
            $sellingPrice = (float) ($row->price ?? 0);
            $now = now();

            $batchId = DB::table('inventory_batches')->insertGetId([
                'product_id' => $row->product_id,
                'variant_id' => $row->sku_id,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'expiry_date' => null,
                'source_type' => 'opening_stock',
                'source_id' => null,
                'batch_reference' => 'OPENING-STOCK-SKU-' . $row->sku_id,
                'note' => 'Backfilled from existing stock on hand.',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('inventory_ledger_entries')->insert([
                'product_id' => $row->product_id,
                'variant_id' => $row->sku_id,
                'product_option_id' => $row->sku_id,
                'movement_type' => 'opening_stock',
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'balance_after' => (int) $row->on_hand,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'reference_type' => 'inventory_batch',
                'reference_id' => $batchId,
                'note' => 'Backfilled from existing stock on hand.',
                'performed_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    });

    $this->info('Opening inventory batches created successfully.');
    return self::SUCCESS;
})->purpose('Create opening inventory batches for existing stock that has no batch backing');

