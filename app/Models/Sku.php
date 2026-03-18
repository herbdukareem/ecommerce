<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Stock;
use App\Models\Warehouse;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku_code', 'price', 'cost', 'weight', 'length', 'width', 'height', 'active',
        'stock_quantity', 'attributes', // New fields for variant management
    ];

    protected $casts = [
        'active' => 'boolean',
        'attributes' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (Sku $sku) {
            $sku->loadMissing('product.vendor');

            $vendorId = $sku->product?->vendor_id;
            if (!$vendorId) {
                return;
            }

            $warehouse = Warehouse::firstOrCreate(
                ['vendor_id' => $vendorId, 'name' => 'Default Warehouse'],
                ['location_id' => null]
            );

            Stock::firstOrCreate(
                ['sku_id' => $sku->id, 'warehouse_id' => $warehouse->id],
                ['on_hand' => max(0, (int) ($sku->stock_quantity ?? 0)), 'reserved' => 0]
            );
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Attribute values defining this SKU.
     */
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'sku_attribute_value');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}