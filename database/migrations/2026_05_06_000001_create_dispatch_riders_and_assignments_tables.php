<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('delivery_partner_id')->nullable()->constrained('delivery_partners')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('vehicle_type', 80)->nullable();
            $table->string('vehicle_plate_number', 80)->nullable();
            $table->string('vehicle_image_path')->nullable();
            $table->string('availability_status', 40)->default('available');
            $table->string('status', 40)->default('active');
            $table->timestamps();

            $table->index(['status', 'availability_status']);
            $table->index('delivery_partner_id');
        });

        Schema::create('dispatch_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('dispatch_rider_id')->constrained('dispatch_riders')->cascadeOnDelete();
            $table->foreignId('delivery_partner_id')->nullable()->constrained('delivery_partners')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_dispatch_rider_id')->nullable()->constrained('dispatch_riders')->nullOnDelete();
            $table->string('status', 40)->default('assigned');
            $table->text('rejection_reason')->nullable();
            $table->text('issue_note')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['dispatch_rider_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'dispatch_rider_id')) {
                $table->foreignId('dispatch_rider_id')->nullable()->after('delivery_partner_id')->constrained('dispatch_riders')->nullOnDelete();
                $table->index(['dispatch_rider_id', 'delivery_status']);
            }
        });

        Schema::table('delivery_partners', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_partners', 'contact_photo_path')) {
                $table->string('contact_photo_path')->nullable()->after('vehicle_type');
            }
            if (!Schema::hasColumn('delivery_partners', 'vehicle_image_path')) {
                $table->string('vehicle_image_path')->nullable()->after('contact_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_partners', 'vehicle_image_path')) {
                $table->dropColumn('vehicle_image_path');
            }
            if (Schema::hasColumn('delivery_partners', 'contact_photo_path')) {
                $table->dropColumn('contact_photo_path');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'dispatch_rider_id')) {
                $table->dropIndex(['dispatch_rider_id', 'delivery_status']);
                $table->dropConstrainedForeignId('dispatch_rider_id');
            }
        });

        Schema::dropIfExists('dispatch_assignments');
        Schema::dropIfExists('dispatch_riders');
    }
};
