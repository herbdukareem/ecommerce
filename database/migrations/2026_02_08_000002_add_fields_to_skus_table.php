<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            if (!Schema::hasColumn('skus', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('price');
            }
            if (!Schema::hasColumn('skus', 'attributes')) {
                $table->json('attributes')->nullable()->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'attributes']);
        });
    }
};

