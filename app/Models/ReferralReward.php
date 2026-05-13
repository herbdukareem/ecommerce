<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    protected $fillable = [
        'referral_id',
        'order_id',
        'referrer_id',
        'referred_user_id',
        'basis',
        'basis_value',
        'eligible_order_amount',
        'amount',
        'status',
        'approved_at',
        'paid_at',
        'cancelled_at',
        'reversed_at',
        'metadata',
    ];

    protected $casts = [
        'basis_value' => 'decimal:4',
        'eligible_order_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }
}
