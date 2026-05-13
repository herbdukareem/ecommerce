<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $name
 * @property string $email
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Keep Spatie role/permission checks on the same guard used by seeded records.
     */
    protected string $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_vendor',
        'email_verified_at',
        'phone',
        'address',
        'city',
        'state',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_vendor' => 'boolean',
    ];

    /**
     * Append is_verified to JSON responses
     */
    protected $appends = ['is_verified'];

    /**
     * Check if user is verified
     */
    public function getIsVerifiedAttribute()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get products created by this vendor
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    /**
     * Get orders placed by this user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get warehouses owned by this vendor
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'vendor_id');
    }

    public function dispatchRider()
    {
        return $this->hasOne(DispatchRider::class);
    }

    public function referralCodes()
    {
        return $this->hasMany(ReferralCode::class);
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referralReceived()
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    public function referralRewards()
    {
        return $this->hasMany(ReferralReward::class, 'referrer_id');
    }

    public function rewardWallet()
    {
        return $this->hasOne(RewardWallet::class);
    }

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }
}
