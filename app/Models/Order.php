<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shipping_address_id',
        'delivery_partner_id',
        'shipping_zone_id',
        'shipping_method_id',
        'status',
        'payment_status',
        'delivery_status',
        'subtotal',
        'shipping_cost',
        'delivery_fee',
        'tax',
        'total',
        'placed_at',
        'assigned_at',
        'shipped_at',
        'delivered_at',
        'delivery_tracking_code',
        'dispatch_note',
        'delivery_snapshot',
        'delivery_address_snapshot',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'assigned_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_snapshot' => 'array',
        'delivery_address_snapshot' => 'array',
    ];

    public const DELIVERY_STATUS_TRANSITIONS = [
        'pending_assignment' => ['assigned', 'cancelled'],
        'assigned' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['in_transit', 'delivery_failed', 'returned'],
        'in_transit' => ['delivered', 'delivery_failed', 'returned'],
        'delivery_failed' => ['in_transit', 'returned', 'cancelled'],
        'returned' => ['cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fulfillments()
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }

    public function canTransitionDeliveryStatusTo(string $nextStatus): bool
    {
        $current = $this->delivery_status ?: 'pending_assignment';
        return in_array($nextStatus, self::DELIVERY_STATUS_TRANSITIONS[$current] ?? [], true);
    }
}