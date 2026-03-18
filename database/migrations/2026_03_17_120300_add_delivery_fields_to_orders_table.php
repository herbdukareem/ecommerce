<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_partner_id')->nullable()->after('shipping_address_id')->constrained('delivery_partners')->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->after('delivery_partner_id')->constrained('shipping_zones')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_zone_id')->constrained('shipping_methods')->nullOnDelete();
            $table->string('delivery_status')->default('pending_assignment')->after('payment_status');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('shipping_cost');
            $table->timestamp('assigned_at')->nullable()->after('placed_at');
            $table->timestamp('shipped_at')->nullable()->after('assigned_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->string('delivery_tracking_code')->nullable()->after('delivered_at');
            $table->text('dispatch_note')->nullable()->after('delivery_tracking_code');
            $table->json('delivery_snapshot')->nullable()->after('dispatch_note');
            $table->json('delivery_address_snapshot')->nullable()->after('delivery_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_partner_id');
            $table->dropConstrainedForeignId('shipping_zone_id');
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn([
                'delivery_status',
                'delivery_fee',
                'assigned_at',
                'shipped_at',
                'delivered_at',
                'delivery_tracking_code',
                'dispatch_note',
                'delivery_snapshot',
                'delivery_address_snapshot',
            ]);
        });
    }
};
