<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        private readonly ReferralSettingsService $settingsService,
        private readonly RewardWalletService $walletService
    ) {
    }

    public function activeCodeFor(User $user): ReferralCode
    {
        $existing = $user->referralCodes()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return ReferralCode::create([
            'user_id' => $user->id,
            'code' => $this->generateCode($user),
            'status' => 'active',
        ]);
    }

    public function findActiveCode(?string $rawCode): ?ReferralCode
    {
        $code = strtoupper(trim((string) $rawCode));
        if ($code === '') {
            return null;
        }

        $referralCode = ReferralCode::query()->whereRaw('UPPER(code) = ?', [$code])->first();

        return $referralCode && $referralCode->isActive() ? $referralCode : null;
    }

    public function linkVerifiedUser(User $newUser, ?string $rawCode): ?Referral
    {
        $settings = $this->settingsService->settings();
        if (!$settings['referral_enabled']) {
            return null;
        }

        $code = $this->findActiveCode($rawCode);
        if (!$code || (int) $code->user_id === (int) $newUser->id) {
            return null;
        }

        return DB::transaction(function () use ($newUser, $code) {
            if (Referral::query()->where('referred_user_id', $newUser->id)->exists()) {
                return null;
            }

            $referral = Referral::create([
                'referrer_id' => $code->user_id,
                'referred_user_id' => $newUser->id,
                'referral_code_id' => $code->id,
                'status' => 'registered',
            ]);

            $code->increment('uses_count');

            return $referral;
        }, 3);
    }

    public function handleOrderEvent(Order $order, string $trigger): ?ReferralReward
    {
        $settings = $this->settingsService->settings();
        if (!$settings['referral_enabled'] || $settings['referral_trigger'] !== $trigger) {
            return null;
        }

        if (!$this->isOrderEligible($order, $settings)) {
            return null;
        }

        $referral = Referral::query()
            ->where('referred_user_id', $order->user_id)
            ->with('referralCode')
            ->first();

        if (!$referral || (int) $referral->referrer_id === (int) $order->user_id) {
            return null;
        }

        return DB::transaction(function () use ($order, $referral, $settings) {
            $existing = ReferralReward::query()
                ->where('referral_id', $referral->id)
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($settings['referral_first_order_only'] && ReferralReward::query()->where('referral_id', $referral->id)->exists()) {
                return null;
            }

            $amount = $this->rewardAmount((float) $order->total, $settings);
            if ($amount <= 0) {
                return null;
            }

            $status = $settings['referral_approval_mode'] === 'automatic' ? 'approved' : 'pending';
            $reward = ReferralReward::create([
                'referral_id' => $referral->id,
                'order_id' => $order->id,
                'referrer_id' => $referral->referrer_id,
                'referred_user_id' => $referral->referred_user_id,
                'basis' => $settings['referral_reward_basis'],
                'basis_value' => $settings['referral_reward_value'],
                'eligible_order_amount' => (float) $order->total,
                'amount' => $amount,
                'status' => $status,
                'approved_at' => $status === 'approved' ? now() : null,
                'metadata' => [
                    'settings_snapshot' => $settings,
                    'trigger' => $settings['referral_trigger'],
                ],
            ]);

            $referral->update([
                'status' => 'qualified',
                'qualified_order_id' => $order->id,
                'qualified_at' => now(),
            ]);

            if ($status === 'approved' && $settings['referral_wallet_redemption_enabled']) {
                $this->walletService->credit(User::findOrFail($referral->referrer_id), $amount, $reward);
            }

            return $reward;
        }, 3);
    }

    public function cancelRewardsForOrder(Order $order, string $status = 'cancelled'): void
    {
        DB::transaction(function () use ($order, $status) {
            $rewards = ReferralReward::query()
                ->where('order_id', $order->id)
                ->whereNotIn('status', ['cancelled', 'reversed'])
                ->lockForUpdate()
                ->get();

            foreach ($rewards as $reward) {
                $previousStatus = $reward->status;
                $reward->update([
                    'status' => $status === 'refunded' ? 'reversed' : 'cancelled',
                    'cancelled_at' => $status === 'refunded' ? null : now(),
                    'reversed_at' => $status === 'refunded' ? now() : null,
                ]);

                if (in_array($previousStatus, ['approved', 'paid', 'redeemed'], true)) {
                    $this->walletService->reverse(User::findOrFail($reward->referrer_id), (float) $reward->amount, $reward);
                }
            }
        }, 3);
    }

    protected function isOrderEligible(Order $order, array $settings): bool
    {
        if ((float) $order->total < (float) $settings['referral_minimum_order_amount']) {
            return false;
        }

        if ($settings['referral_trigger'] === 'paid_order') {
            return $order->payment_status === 'paid';
        }

        return $order->payment_status === 'paid'
            && ($order->status === 'delivered' || $order->delivery_status === 'delivered');
    }

    protected function rewardAmount(float $orderTotal, array $settings): float
    {
        if ($settings['referral_reward_basis'] === 'percentage') {
            return round($orderTotal * ((float) $settings['referral_reward_value'] / 100), 2);
        }

        return round((float) $settings['referral_reward_value'], 2);
    }

    protected function generateCode(User $user): string
    {
        do {
            $base = strtoupper(Str::slug(Str::limit($user->name ?: 'USER', 6, '')));
            $code = preg_replace('/[^A-Z0-9]/', '', $base) . strtoupper(Str::random(6));
        } while (ReferralCode::query()->where('code', $code)->exists());

        return $code;
    }
}
