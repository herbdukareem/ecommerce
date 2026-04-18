<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'product_option_id',
        'movement_type',
        'quantity_in',
        'quantity_out',
        'balance_after',
        'cost_price',
        'selling_price',
        'reference_type',
        'reference_id',
        'note',
        'performed_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class, 'variant_id');
    }

    public function productOption()
    {
        return $this->belongsTo(Sku::class, 'product_option_id');
    }
}
