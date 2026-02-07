<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('attribute_product', function (Blueprint $table) {
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['attribute_id', 'product_id']);
        });
        Schema::create('sku_attribute_value', function (Blueprint $table) {
            $table->foreignId('sku_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['sku_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_attribute_value');
        Schema::dropIfExists('attribute_product');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};