<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity_received',
        'quantity_remaining',
        'cost_price',
        'selling_price',
        'expiry_date',
        'source_type',
        'source_id',
        'batch_reference',
        'note',
        'created_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class, 'variant_id');
    }

    public function allocations()
    {
        return $this->hasMany(OrderItemInventoryAllocation::class);
    }
}
