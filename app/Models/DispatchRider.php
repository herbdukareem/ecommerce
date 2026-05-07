<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DispatchRider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'delivery_partner_id',
        'phone',
        'profile_photo_path',
        'vehicle_type',
        'vehicle_plate_number',
        'vehicle_image_path',
        'availability_status',
        'status',
    ];

    protected $appends = [
        'profile_photo_url',
        'vehicle_image_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function assignments()
    {
        return $this->hasMany(DispatchAssignment::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? Storage::url($this->profile_photo_path) : null;
    }

    public function getVehicleImageUrlAttribute(): ?string
    {
        return $this->vehicle_image_path ? Storage::url($this->vehicle_image_path) : null;
    }
}
