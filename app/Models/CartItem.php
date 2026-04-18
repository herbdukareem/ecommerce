<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'sku_id',
        'product_option_id',
        'quantity',
        'price',
        'product_name_snapshot',
        'option_label_snapshot',
        'image_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the cart that owns the item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the SKU for this cart item.
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function productOption()
    {
        return $this->belongsTo(Sku::class, 'product_option_id');
    }

    /**
     * Get the subtotal for this item.
     */
    public function subtotal()
    {
        return $this->price * $this->quantity;
    }
}

