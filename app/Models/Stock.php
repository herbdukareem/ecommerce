<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $fillable = [
        'sku_id', 'warehouse_id', 'on_hand', 'reserved',
    ];

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}