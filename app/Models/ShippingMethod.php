<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_fee',
        'per_kg_surcharge',
        'express_surcharge',
        'free_shipping_threshold',
        'supports_cod',
        'is_pickup',
        'active',
    ];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'per_kg_surcharge' => 'decimal:2',
        'express_surcharge' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'supports_cod' => 'boolean',
        'is_pickup' => 'boolean',
        'active' => 'boolean',
    ];

    public function rules()
    {
        return $this->hasMany(ShippingZoneRule::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_method_id');
    }
}