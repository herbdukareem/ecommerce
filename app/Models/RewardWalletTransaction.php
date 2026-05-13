<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardWalletTransaction extends Model
{
    protected $fillable = [
        'reward_wallet_id',
        'referral_reward_id',
        'order_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];
}
