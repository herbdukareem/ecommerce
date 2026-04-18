<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_options')->default(false)->after('status');
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->string('option_label')->nullable()->after('sku_code');
            $table->string('option_code')->nullable()->after('option_label');
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('price');
            $table->decimal('cost_price', 12, 2)->nullable()->after('cost');
            $table->integer('low_stock_threshold')->nullable()->after('stock_quantity');
            $table->string('image_path')->nullable()->after('attributes');
            $table->integer('sort_order')->default(0)->after('image_path');
            $table->string('unit', 50)->nullable()->after('weight');
            $table->json('metadata')->nullable()->after('unit');
            $table->foreignId('created_by')->nullable()->after('metadata')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            $table->index(['product_id', 'active', 'sort_order'], 'skus_product_active_sort_idx');
            $table->unique(['product_id', 'option_label'], 'skus_product_option_label_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_option_id')->nullable()->after('sku_id')->constrained('skus')->nullOnDelete();
            $table->string('product_name_snapshot')->nullable()->after('price');
            $table->string('option_label_snapshot')->nullable()->after('product_name_snapshot');
            $table->string('image_snapshot')->nullable()->after('option_label_snapshot');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_option_id')->nullable()->after('sku_id')->constrained('skus')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('product_option_id')->constrained('products')->nullOnDelete();
            $table->string('product_name_snapshot')->nullable()->after('product_id');
            $table->string('option_label_snapshot')->nullable()->after('product_name_snapshot');
            $table->string('image_snapshot')->nullable()->after('option_label_snapshot');
        });

        Schema::table('inventory_ledger_entries', function (Blueprint $table) {
            $table->foreignId('product_option_id')->nullable()->after('variant_id')->constrained('skus')->nullOnDelete();
            $table->index(['product_option_id', 'created_at'], 'inventory_ledger_option_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_ledger_entries', function (Blueprint $table) {
            $table->dropIndex('inventory_ledger_option_created_idx');
            $table->dropConstrainedForeignId('product_option_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_option_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['product_name_snapshot', 'option_label_snapshot', 'image_snapshot']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_option_id');
            $table->dropColumn(['product_name_snapshot', 'option_label_snapshot', 'image_snapshot']);
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->dropUnique('skus_product_option_label_unique');
            $table->dropIndex('skus_product_active_sort_idx');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'option_label',
                'option_code',
                'compare_at_price',
                'cost_price',
                'low_stock_threshold',
                'image_path',
                'sort_order',
                'unit',
                'metadata',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_options');
        });
    }
};
