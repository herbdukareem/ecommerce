<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingProviderAccount extends Model
{
    use HasFactory;
    protected $fillable = ['provider', 'credentials'];

    protected $casts = [
        'credentials' => 'array',
    ];
}