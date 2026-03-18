<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'name',
        'phone',
        'email',
        'country',
        'country_code',
        'country_name',
        'state',
        'state_code',
        'state_name',
        'city',
        'city_name',
        'area_or_district',
        'address_line_1',
        'address_line_2',
        'landmark',
        'postal_code',
        'delivery_note',
        'latitude',
        'longitude',
        'is_default',
        'zip',
        'line1',
        'line2',
        'lat',
        'lng',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getZipAttribute($value)
    {
        return $value ?: $this->postal_code;
    }

    public function getFullNameAttribute($value)
    {
        return $value ?: $this->name;
    }

    public function getLine1Attribute($value)
    {
        return $value ?: $this->address_line_1;
    }

    public function getLine2Attribute($value)
    {
        return $value ?: $this->address_line_2;
    }

    protected static function booted(): void
    {
        static::saving(function (Address $address) {
            $address->name = $address->full_name ?: $address->name;
            $address->full_name = $address->full_name ?: $address->name;

            $address->country_name = $address->country_name ?: $address->country;
            $address->country = $address->country ?: $address->country_name;
            $address->state_name = $address->state_name ?: $address->state;
            $address->state = $address->state ?: $address->state_name;
            $address->city_name = $address->city_name ?: $address->city;
            $address->city = $address->city ?: $address->city_name;

            $address->line1 = $address->address_line_1 ?: $address->line1;
            $address->line2 = $address->address_line_2 ?: $address->line2;
            $address->zip = $address->postal_code ?: $address->zip;

            $address->lat = $address->latitude ?: $address->lat;
            $address->lng = $address->longitude ?: $address->lng;
        });
    }
}