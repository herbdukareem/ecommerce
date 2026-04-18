<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'order_id', 'sku_id', 'product_option_id', 'product_id', 'quantity', 'price_snapshot', 'product_name_snapshot', 'option_label_snapshot', 'image_snapshot', 'unit_cost_at_sale', 'total_cost_at_sale', 'unit_price_at_sale', 'total_price_at_sale', 'weight_snapshot', 'length_snapshot', 'width_snapshot', 'height_snapshot',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function productOption()
    {
        return $this->belongsTo(Sku::class, 'product_option_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryAllocations()
    {
        return $this->hasMany(OrderItemInventoryAllocation::class);
    }
}