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
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 20)->default('simple')->after('has_options')->index();
            }
        });

        DB::table('products')
            ->whereNull('product_type')
            ->orWhere('product_type', '')
            ->update(['product_type' => 'simple']);

        DB::table('products')
            ->where('has_options', true)
            ->where('product_type', 'simple')
            ->update(['product_type' => 'variant']);

        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->decimal('conversion_factor', 15, 6)->default(1);
                $table->boolean('is_base')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('basket_components')) {
            Schema::create('basket_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('basket_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('component_sku_id')->constrained('skus')->restrictOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->decimal('quantity', 15, 4);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->unique(['basket_product_id', 'component_sku_id'], 'basket_components_product_sku_unique');
                $table->index('component_sku_id', 'basket_components_sku_idx');
            });
        }

        if (!Schema::hasTable('order_item_components')) {
            Schema::create('order_item_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->foreignId('basket_product_id')->constrained('products')->restrictOnDelete();
                $table->foreignId('component_sku_id')->constrained('skus')->restrictOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->string('unit_name')->nullable();
                $table->decimal('quantity_per_basket', 15, 4);
                $table->decimal('basket_quantity', 15, 4);
                $table->decimal('total_quantity', 15, 4);
                $table->decimal('conversion_factor', 15, 6)->default(1);
                $table->decimal('base_quantity', 15, 4)->nullable();
                $table->string('component_name_snapshot')->nullable();
                $table->string('component_sku_snapshot')->nullable();
                $table->decimal('unit_cost_at_sale', 12, 2)->nullable();
                $table->decimal('total_cost_at_sale', 12, 2)->nullable();
                $table->boolean('inventory_reserved')->default(false);
                $table->timestamp('reserved_at')->nullable();
                $table->boolean('inventory_committed')->default(false);
                $table->timestamp('committed_at')->nullable();
                $table->boolean('inventory_released')->default(false);
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->index(['order_item_id', 'component_sku_id'], 'oic_order_item_sku_idx');
                $table->index(['inventory_reserved', 'inventory_committed'], 'oic_reserved_committed_idx');
            });
        } elseif (!$this->indexExists('order_item_components', 'oic_reserved_committed_idx')) {
            Schema::table('order_item_components', function (Blueprint $table) {
                $table->index(['inventory_reserved', 'inventory_committed'], 'oic_reserved_committed_idx');
            });
        }

        Schema::table('order_item_inventory_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('order_item_inventory_allocations', 'order_item_component_id')) {
                $table->foreignId('order_item_component_id')
                    ->nullable()
                    ->after('order_item_id')
                    ->constrained('order_item_components')
                    ->nullOnDelete();
                $table->index('order_item_component_id', 'oiia_component_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_item_inventory_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('order_item_inventory_allocations', 'order_item_component_id')) {
                $table->dropConstrainedForeignId('order_item_component_id');
            }
        });

        Schema::dropIfExists('order_item_components');
        Schema::dropIfExists('basket_components');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });

        Schema::dropIfExists('units');
    }

    private function indexExists(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'select count(*) as aggregate from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ?',
            [$table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
