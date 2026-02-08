<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'site_name', 'value' => 'Ashlab E-commerce', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_description', 'value' => 'Your one-stop shop for everything', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_email', 'value' => 'info@ashlab.com', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency', 'value' => 'NGN', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_symbol', 'value' => '₦', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tax_rate', 'value' => '7.5', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'enable_reviews', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'enable_wishlist', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'enable_coupons', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

