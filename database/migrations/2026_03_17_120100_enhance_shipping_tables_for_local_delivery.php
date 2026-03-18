<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->json('coverage_states')->nullable()->after('region');
            $table->json('coverage_cities')->nullable()->after('coverage_states');
            $table->json('coverage_areas')->nullable()->after('coverage_cities');
            $table->decimal('default_fee', 12, 2)->nullable()->after('coverage_areas');
            $table->boolean('is_fallback')->default(false)->after('default_fee');
            $table->boolean('active')->default(true)->after('is_fallback');
            $table->text('description')->nullable()->after('active');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->text('description')->nullable()->after('code');
            $table->decimal('base_fee', 12, 2)->default(0)->after('description');
            $table->decimal('per_kg_surcharge', 12, 2)->default(0)->after('base_fee');
            $table->decimal('express_surcharge', 12, 2)->default(0)->after('per_kg_surcharge');
            $table->decimal('free_shipping_threshold', 12, 2)->nullable()->after('express_surcharge');
            $table->boolean('supports_cod')->default(false)->after('free_shipping_threshold');
            $table->boolean('is_pickup')->default(false)->after('supports_cod');
        });

        Schema::table('shipping_zone_rules', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_zone_id')->constrained('shipping_methods')->nullOnDelete();
            $table->unsignedInteger('priority')->default(100)->after('config');
            $table->boolean('active')->default(true)->after('priority');
        });

        DB::table('shipping_methods')->orderBy('id')->get()->each(function ($method) {
            $slug = Str::slug($method->name, '_');
            DB::table('shipping_methods')->where('id', $method->id)->update([
                'code' => $slug ?: ('method_' . $method->id),
                'base_fee' => 1500,
            ]);
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_zone_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn(['priority', 'active']);
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code',
                'description',
                'base_fee',
                'per_kg_surcharge',
                'express_surcharge',
                'free_shipping_threshold',
                'supports_cod',
                'is_pickup',
            ]);
        });

        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn([
                'coverage_states',
                'coverage_cities',
                'coverage_areas',
                'default_fee',
                'is_fallback',
                'active',
                'description',
            ]);
        });
    }
};
