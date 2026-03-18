<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'coupon_discount',
    ];

    protected $casts = [
        'coupon_discount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Calculate the total price of all items in the cart.
     */
    public function total()
    {
        return max(0, $this->subtotal() - $this->discount());
    }

    public function subtotal()
    {
        return $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function discount()
    {
        return (float) ($this->coupon_discount ?? 0);
    }

    /**
     * Get the total number of items in the cart.
     */
    public function itemCount()
    {
        return $this->items->sum('quantity');
    }
}

