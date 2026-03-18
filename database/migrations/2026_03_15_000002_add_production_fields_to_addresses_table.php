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
            $table->string('name')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('name');
            $table->string('address_line_1')->nullable()->after('city');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('postal_code')->nullable()->after('address_line_2');
            $table->boolean('is_default')->default(false)->after('postal_code');
        });

        DB::statement("UPDATE addresses SET address_line_1 = COALESCE(address_line_1, line1)");
        DB::statement("UPDATE addresses SET address_line_2 = COALESCE(address_line_2, line2)");
        DB::statement("UPDATE addresses SET postal_code = COALESCE(postal_code, zip)");
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'phone',
                'address_line_1',
                'address_line_2',
                'postal_code',
                'is_default',
            ]);
        });
    }
};
