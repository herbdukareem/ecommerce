<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add name and price as aliases for title and base_price
            $table->string('name')->nullable()->after('vendor_id');
            $table->decimal('price', 12, 2)->nullable()->after('base_price');
            $table->string('image')->nullable()->after('description');
        });

        // Copy existing data
        DB::statement('UPDATE products SET name = title WHERE name IS NULL');
        DB::statement('UPDATE products SET price = base_price WHERE price IS NULL');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name', 'price', 'image']);
        });
    }
};

