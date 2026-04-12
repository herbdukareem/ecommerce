<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('shipping_address_id')->constrained('operation_cities')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('city_id')->constrained('operation_areas')->nullOnDelete();
            $table->foreignId('dispatch_time_slot_id')->nullable()->after('area_id')->constrained('dispatch_time_slots')->nullOnDelete();

            $table->string('city_name')->nullable()->after('dispatch_time_slot_id');
            $table->string('area_name')->nullable()->after('city_name');
            $table->string('dispatch_time_label')->nullable()->after('area_name');
            $table->time('dispatch_start_time')->nullable()->after('dispatch_time_label');
            $table->time('dispatch_end_time')->nullable()->after('dispatch_start_time');

            $table->string('payment_mode')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_mode');
            $table->text('order_note')->nullable()->after('payment_reference');
            $table->text('internal_note')->nullable()->after('order_note');

            $table->foreignId('created_by_admin_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('dispatch_time_slot_id');
            $table->dropConstrainedForeignId('created_by_admin_id');
            $table->dropColumn([
                'city_name',
                'area_name',
                'dispatch_time_label',
                'dispatch_start_time',
                'dispatch_end_time',
                'payment_mode',
                'payment_reference',
                'order_note',
                'internal_note',
            ]);
        });
    }
};
