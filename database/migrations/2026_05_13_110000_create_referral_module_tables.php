<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('pending_customer_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_customer_registrations', 'referral_code')) {
                $table->string('referral_code')->nullable()->after('ip_address')->index();
            }
            if (!Schema::hasColumn('pending_customer_registrations', 'referral_code_id')) {
                $table->foreignId('referral_code_id')->nullable()->after('referral_code')->constrained('referral_codes')->nullOnDelete();
            }
            if (!Schema::hasColumn('pending_customer_registrations', 'referral_ip_address')) {
                $table->string('referral_ip_address', 45)->nullable()->after('referral_code_id');
            }
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referral_code_id')->constrained('referral_codes')->cascadeOnDelete();
            $table->string('status', 30)->default('registered');
            $table->foreignId('qualified_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->unique('referred_user_id');
            $table->index(['referrer_id', 'status']);
        });

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('basis', 30);
            $table->decimal('basis_value', 15, 4);
            $table->decimal('eligible_order_amount', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->string('status', 30)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['referral_id', 'order_id']);
            $table->index(['referrer_id', 'status']);
        });

        Schema::create('reward_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('reward_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_wallet_id')->constrained('reward_wallets')->cascadeOnDelete();
            $table->foreignId('referral_reward_id')->nullable()->constrained('referral_rewards')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reward_wallet_id', 'type']);
        });

        $defaults = [
            'referral_enabled' => '0',
            'referral_reward_basis' => 'fixed',
            'referral_reward_value' => '0',
            'referral_first_order_only' => '1',
            'referral_minimum_order_amount' => '0',
            'referral_trigger' => 'paid_order',
            'referral_approval_mode' => 'manual',
            'referral_wallet_redemption_enabled' => '0',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_wallet_transactions');
        Schema::dropIfExists('reward_wallets');
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referrals');

        Schema::table('pending_customer_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pending_customer_registrations', 'referral_code_id')) {
                $table->dropConstrainedForeignId('referral_code_id');
            }
            if (Schema::hasColumn('pending_customer_registrations', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
            if (Schema::hasColumn('pending_customer_registrations', 'referral_ip_address')) {
                $table->dropColumn('referral_ip_address');
            }
        });

        Schema::dropIfExists('referral_codes');

        DB::table('settings')->whereIn('key', [
            'referral_enabled',
            'referral_reward_basis',
            'referral_reward_value',
            'referral_first_order_only',
            'referral_minimum_order_amount',
            'referral_trigger',
            'referral_approval_mode',
            'referral_wallet_redemption_enabled',
        ])->delete();
    }
};
