<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'company_name',
        'coverage_states',
        'coverage_cities',
        'coverage_areas',
        'pricing_notes',
        'status',
        'vehicle_type',
        'notes',
    ];

    protected $casts = [
        'coverage_states' => 'array',
        'coverage_cities' => 'array',
        'coverage_areas' => 'array',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_partner_id');
    }
}
