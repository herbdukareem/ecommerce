<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost_at_sale', 12, 2)->nullable()->after('price_snapshot');
            $table->decimal('total_cost_at_sale', 12, 2)->nullable()->after('unit_cost_at_sale');
            $table->decimal('unit_price_at_sale', 12, 2)->nullable()->after('total_cost_at_sale');
            $table->decimal('total_price_at_sale', 12, 2)->nullable()->after('unit_price_at_sale');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_cost_at_sale',
                'total_cost_at_sale',
                'unit_price_at_sale',
                'total_price_at_sale',
            ]);
        });
    }
};
