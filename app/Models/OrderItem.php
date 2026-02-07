<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'order_id', 'sku_id', 'quantity', 'price_snapshot', 'weight_snapshot', 'length_snapshot', 'width_snapshot', 'height_snapshot',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}