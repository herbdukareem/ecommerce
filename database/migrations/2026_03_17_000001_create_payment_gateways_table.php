<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('mode')->default('sandbox');
            $table->json('supported_currencies')->nullable();
            $table->string('fee_type')->nullable();
            $table->decimal('fee_value', 10, 2)->default(0);
            $table->json('extra_config')->nullable();
            $table->timestamps();
        });

        DB::table('payment_gateways')->insert([
            [
                'provider' => 'paystack',
                'display_name' => 'Paystack',
                'description' => 'Paystack card and transfer payments',
                'is_enabled' => true,
                'is_visible' => true,
                'is_default' => true,
                'sort_order' => 1,
                'mode' => 'sandbox',
                'supported_currencies' => json_encode(['NGN']),
                'fee_type' => null,
                'fee_value' => 0,
                'extra_config' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'flutterwave',
                'display_name' => 'Flutterwave',
                'description' => 'Flutterwave multi-channel payments',
                'is_enabled' => false,
                'is_visible' => true,
                'is_default' => false,
                'sort_order' => 2,
                'mode' => 'sandbox',
                'supported_currencies' => json_encode(['NGN']),
                'fee_type' => null,
                'fee_value' => 0,
                'extra_config' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
