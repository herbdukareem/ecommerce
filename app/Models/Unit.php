<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'code',
        'conversion_factor',
        'is_base',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'is_base' => 'boolean',
    ];
}
