<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'display_name',
        'description',
        'is_enabled',
        'is_visible',
        'is_default',
        'sort_order',
        'mode',
        'supported_currencies',
        'fee_type',
        'fee_value',
        'extra_config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_visible' => 'boolean',
        'is_default' => 'boolean',
        'supported_currencies' => 'array',
        'extra_config' => 'array',
        'fee_value' => 'decimal:2',
    ];
}
