<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pay_on_delivery_enabled')) {
                $table->boolean('pay_on_delivery_enabled')->default(false)->after('product_type')->index();
            }
        });

        $defaults = [
            'pay_on_delivery_enabled' => '0',
            'pay_on_delivery_min_order_amount' => '0',
            'pay_on_delivery_max_order_amount' => '0',
            'pay_on_delivery_allowed_city_ids' => '[]',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'pay_on_delivery_enabled')) {
                $table->dropColumn('pay_on_delivery_enabled');
            }
        });

        DB::table('settings')->whereIn('key', [
            'pay_on_delivery_enabled',
            'pay_on_delivery_min_order_amount',
            'pay_on_delivery_max_order_amount',
            'pay_on_delivery_allowed_city_ids',
        ])->delete();
    }
};
