<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referral_code_id',
        'status',
        'qualified_order_id',
        'qualified_at',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function rewards()
    {
        return $this->hasMany(ReferralReward::class);
    }
}
