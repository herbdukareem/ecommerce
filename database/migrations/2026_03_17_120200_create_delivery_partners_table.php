<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->json('coverage_states')->nullable();
            $table->json('coverage_cities')->nullable();
            $table->json('coverage_areas')->nullable();
            $table->text('pricing_notes')->nullable();
            $table->string('status')->default('active');
            $table->string('vehicle_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
