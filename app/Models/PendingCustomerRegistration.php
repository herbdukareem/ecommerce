<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingCustomerRegistration extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'ip_address',
        'referral_code',
        'referral_code_id',
        'referral_ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
