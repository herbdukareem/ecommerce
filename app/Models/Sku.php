<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id', 'sku_code', 'price', 'cost', 'weight', 'length', 'width', 'height', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

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