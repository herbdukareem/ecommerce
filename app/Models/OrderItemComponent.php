<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemComponent extends Model
{
    protected $fillable = [
        'order_item_id',
        'basket_product_id',
        'component_sku_id',
        'unit_id',
        'unit_name',
        'quantity_per_basket',
        'basket_quantity',
        'total_quantity',
        'conversion_factor',
        'base_quantity',
        'component_name_snapshot',
        'component_sku_snapshot',
        'unit_cost_at_sale',
        'total_cost_at_sale',
        'inventory_reserved',
        'reserved_at',
        'inventory_committed',
        'committed_at',
        'inventory_released',
        'released_at',
    ];

    protected $casts = [
        'quantity_per_basket' => 'decimal:4',
        'basket_quantity' => 'decimal:4',
        'total_quantity' => 'decimal:4',
        'conversion_factor' => 'decimal:6',
        'base_quantity' => 'decimal:4',
        'unit_cost_at_sale' => 'decimal:2',
        'total_cost_at_sale' => 'decimal:2',
        'inventory_reserved' => 'boolean',
        'reserved_at' => 'datetime',
        'inventory_committed' => 'boolean',
        'committed_at' => 'datetime',
        'inventory_released' => 'boolean',
        'released_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

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

    public function inventoryAllocations()
    {
        return $this->hasMany(OrderItemInventoryAllocation::class);
    }
}
