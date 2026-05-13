<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasketComponent extends Model
{
    protected $fillable = [
        'basket_product_id',
        'component_sku_id',
        'unit_id',
        'quantity',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
    ];

    public function basketProduct()
    {
        return $this->belongsTo(Product::class, 'basket_product_id');
    }

    public function componentSku()
    {
        return $this->belongsTo(Sku::class, 'component_sku_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
