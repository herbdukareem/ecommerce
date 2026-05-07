<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'contact_photo_path',
        'vehicle_image_path',
        'notes',
    ];

    protected $casts = [
        'coverage_states' => 'array',
        'coverage_cities' => 'array',
        'coverage_areas' => 'array',
    ];

    protected $appends = [
        'contact_photo_url',
        'vehicle_image_url',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_partner_id');
    }

    public function riders()
    {
        return $this->hasMany(DispatchRider::class);
    }

    public function getContactPhotoUrlAttribute(): ?string
    {
        return $this->contact_photo_path ? Storage::url($this->contact_photo_path) : null;
    }

    public function getVehicleImageUrlAttribute(): ?string
    {
        return $this->vehicle_image_path ? Storage::url($this->vehicle_image_path) : null;
    }
}
