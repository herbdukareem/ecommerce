<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_subtotal',
        'max_discount',
        'is_active',
        'starts_at',
        'expires_at',
        'usage_limit',
        'usage_count',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isUsable(float $subtotal): bool
    {
        $now = now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return $subtotal >= (float) $this->min_subtotal;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'fixed') {
            return min($subtotal, (float) $this->discount_value);
        }

        $discount = $subtotal * ((float) $this->discount_value / 100);
        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return min($subtotal, $discount);
    }
}
