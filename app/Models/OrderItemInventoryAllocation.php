<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemInventoryAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'order_item_component_id',
        'inventory_batch_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function orderItemComponent()
    {
        return $this->belongsTo(OrderItemComponent::class);
    }

    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
