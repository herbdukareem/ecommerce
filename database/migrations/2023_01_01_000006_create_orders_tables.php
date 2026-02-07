<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('country');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('line1')->nullable();
            $table->string('line2')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price_snapshot', 12, 2);
            $table->decimal('weight_snapshot', 8, 2);
            $table->decimal('length_snapshot', 8, 2);
            $table->decimal('width_snapshot', 8, 2);
            $table->decimal('height_snapshot', 8, 2);
        });

        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->string('shipment_provider')->nullable();
            $table->string('tracking_no')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
    }
};