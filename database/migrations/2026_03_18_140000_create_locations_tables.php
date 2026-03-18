<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });

        Schema::create('location_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('location_countries')->cascadeOnDelete();
            $table->string('code', 10)->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['country_id', 'name']);
            $table->unique(['country_id', 'code']);
            $table->index(['country_id', 'active', 'sort_order']);
        });

        Schema::create('location_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('location_states')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['state_id', 'name']);
            $table->unique(['state_id', 'code']);
            $table->index(['state_id', 'active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_cities');
        Schema::dropIfExists('location_states');
        Schema::dropIfExists('location_countries');
    }
};
