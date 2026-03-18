<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'code',
        'name',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function state()
    {
        return $this->belongsTo(LocationState::class, 'state_id');
    }
}
