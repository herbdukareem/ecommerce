<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationCountry extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function states()
    {
        return $this->hasMany(LocationState::class, 'country_id');
    }
}
