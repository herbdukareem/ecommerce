<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region'); // Could represent zip range or geographic identifier
            $table->timestamps();
        });

        Schema::create('shipping_zone_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('rule_type'); // e.g., flat, weight_based, price_based
            $table->json('config');
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // e.g., fedex, dhl
            $table->json('credentials');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_provider_accounts');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zone_rules');
        Schema::dropIfExists('shipping_zones');
    }
};