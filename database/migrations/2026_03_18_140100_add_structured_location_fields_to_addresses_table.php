<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('country');
            $table->string('country_name')->nullable()->after('country_code');
            $table->string('state_code', 10)->nullable()->after('state');
            $table->string('state_name')->nullable()->after('state_code');
            $table->string('city_name')->nullable()->after('city');
        });

        DB::statement('UPDATE addresses SET country_name = COALESCE(country_name, country)');
        DB::statement('UPDATE addresses SET state_name = COALESCE(state_name, state)');
        DB::statement('UPDATE addresses SET city_name = COALESCE(city_name, city)');
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'country_code',
                'country_name',
                'state_code',
                'state_name',
                'city_name',
            ]);
        });
    }
};
