<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $fillable = ['user_id', 'code', 'status', 'max_uses', 'uses_count', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->uses_count < $this->max_uses;
    }
}
