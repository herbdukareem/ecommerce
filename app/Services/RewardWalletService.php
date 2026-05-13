<?php

namespace App\Services;

use App\Models\ReferralReward;
use App\Models\RewardWallet;
use App\Models\RewardWalletTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardWalletService
{
    public function credit(User $user, float $amount, ?ReferralReward $reward = null, ?string $description = null): RewardWalletTransaction
    {
        return $this->record($user, 'credit', abs($amount), $reward, null, $description ?: 'Referral reward credit');
    }

    public function debit(User $user, float $amount, ?int $orderId = null, ?string $description = null): RewardWalletTransaction
    {
        return $this->record($user, 'debit', abs($amount), null, $orderId, $description ?: 'Reward wallet debit');
    }

    public function reverse(User $user, float $amount, ?ReferralReward $reward = null, ?string $description = null): RewardWalletTransaction
    {
        return $this->record($user, 'reversal', abs($amount), $reward, null, $description ?: 'Reward wallet reversal');
    }

    protected function record(User $user, string $type, float $amount, ?ReferralReward $reward, ?int $orderId, string $description): RewardWalletTransaction
    {
        return DB::transaction(function () use ($user, $type, $amount, $reward, $orderId, $description) {
            $wallet = RewardWallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
            $wallet = RewardWallet::query()->lockForUpdate()->find($wallet->id);

            $before = (float) $wallet->balance;
            $after = in_array($type, ['debit', 'reversal'], true) ? $before - $amount : $before + $amount;

            if ($type === 'debit' && $after < -0.0001) {
                throw ValidationException::withMessages([
                    'wallet' => ['Insufficient reward wallet balance.'],
                ]);
            }

            $wallet->update(['balance' => $after]);

            return RewardWalletTransaction::create([
                'reward_wallet_id' => $wallet->id,
                'referral_reward_id' => $reward?->id,
                'order_id' => $orderId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $description,
                'metadata' => [
                    'user_id' => $user->id,
                    'referral_reward_id' => $reward?->id,
                ],
            ]);
        }, 3);
    }
}
