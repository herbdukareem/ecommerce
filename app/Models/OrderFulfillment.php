<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderFulfillment extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'warehouse_id', 'shipment_provider', 'tracking_no', 'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}