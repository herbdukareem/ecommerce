<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingZone extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'region',
        'coverage_states',
        'coverage_cities',
        'coverage_areas',
        'default_fee',
        'is_fallback',
        'active',
        'description',
    ];

    protected $casts = [
        'coverage_states' => 'array',
        'coverage_cities' => 'array',
        'coverage_areas' => 'array',
        'default_fee' => 'decimal:2',
        'is_fallback' => 'boolean',
        'active' => 'boolean',
    ];

    public function rules()
    {
        return $this->hasMany(ShippingZoneRule::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_zone_id');
    }
}