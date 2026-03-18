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
            $table->string('full_name')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone');
            $table->string('area_or_district')->nullable()->after('city');
            $table->string('landmark')->nullable()->after('address_line_2');
            $table->text('delivery_note')->nullable()->after('landmark');
            $table->decimal('latitude', 10, 7)->nullable()->after('delivery_note');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        DB::statement('UPDATE addresses SET full_name = COALESCE(full_name, name)');
        DB::statement('UPDATE addresses SET latitude = COALESCE(latitude, lat)');
        DB::statement('UPDATE addresses SET longitude = COALESCE(longitude, lng)');
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'email',
                'area_or_district',
                'landmark',
                'delivery_note',
                'latitude',
                'longitude',
            ]);
        });
    }
};
