<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'status',
        'sort_order',
    ];

    public function areas()
    {
        return $this->hasMany(OperationArea::class, 'city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
