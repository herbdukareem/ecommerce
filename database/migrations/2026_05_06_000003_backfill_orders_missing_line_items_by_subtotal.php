<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('orders')
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->select('orders.id', 'orders.subtotal')
            ->where('orders.subtotal', '>', 0)
            ->groupBy('orders.id', 'orders.subtotal')
            ->havingRaw('COUNT(order_items.id) = 0')
            ->get();

        foreach ($orders as $order) {
            $matchingSkus = DB::table('skus')
                ->join('products', 'products.id', '=', 'skus.product_id')
                ->where('skus.price', $order->subtotal)
                ->select([
                    'skus.id',
                    'skus.product_id',
                    'skus.option_label',
                    'skus.sku_code',
                    'skus.price',
                    'skus.image_path',
                    'skus.weight',
                    'skus.length',
                    'skus.width',
                    'skus.height',
                    'products.title as product_title',
                    'products.image as product_image',
                ])
                ->limit(2)
                ->get();

            if ($matchingSkus->count() !== 1) {
                continue;
            }

            $sku = $matchingSkus->first();
            $payload = [
                'order_id' => $order->id,
                'sku_id' => $sku->id,
                'quantity' => 1,
                'price_snapshot' => $sku->price,
                'weight_snapshot' => $sku->weight ?? 0,
                'length_snapshot' => $sku->length ?? 0,
                'width_snapshot' => $sku->width ?? 0,
                'height_snapshot' => $sku->height ?? 0,
            ];

            if (Schema::hasColumn('order_items', 'product_option_id')) {
                $payload['product_option_id'] = $sku->id;
            }

            if (Schema::hasColumn('order_items', 'product_id')) {
                $payload['product_id'] = $sku->product_id;
            }

            if (Schema::hasColumn('order_items', 'product_name_snapshot')) {
                $payload['product_name_snapshot'] = $sku->product_title;
            }

            if (Schema::hasColumn('order_items', 'option_label_snapshot')) {
                $payload['option_label_snapshot'] = $sku->option_label ?: $sku->sku_code;
            }

            if (Schema::hasColumn('order_items', 'image_snapshot')) {
                $payload['image_snapshot'] = $sku->image_path ?: $sku->product_image;
            }

            DB::table('order_items')->insert($payload);
        }
    }

    public function down(): void
    {
        // Data repair migration. Intentionally not destructive.
    }
};
