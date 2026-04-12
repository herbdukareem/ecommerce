<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 60)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('operation_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('operation_cities')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('delivery_fee', 12, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'name']);
            $table->index(['city_id', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_areas');
        Schema::dropIfExists('operation_cities');
    }
};
