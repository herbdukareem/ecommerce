<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'delivery_fee',
        'status',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
    ];

    public function city()
    {
        return $this->belongsTo(OperationCity::class, 'city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
