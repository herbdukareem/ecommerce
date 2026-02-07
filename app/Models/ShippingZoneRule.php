<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingZoneRule extends Model
{
    use HasFactory;
    protected $fillable = ['shipping_zone_id', 'rule_type', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}