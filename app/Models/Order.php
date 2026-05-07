<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_admin_id',
        'user_id',
        'shipping_address_id',
        'city_id',
        'area_id',
        'dispatch_time_slot_id',
        'delivery_partner_id',
        'dispatch_rider_id',
        'shipping_zone_id',
        'shipping_method_id',
        'city_name',
        'area_name',
        'dispatch_time_label',
        'dispatch_start_time',
        'dispatch_end_time',
        'status',
        'payment_status',
        'payment_mode',
        'payment_reference',
        'order_note',
        'internal_note',
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
        'assigned' => ['accepted', 'rejected', 'picked_up', 'cancelled'],
        'accepted' => ['picked_up', 'delivery_failed', 'cancelled'],
        'rejected' => ['assigned', 'cancelled'],
        'packed' => ['ready_for_dispatch', 'cancelled'],
        'ready_for_dispatch' => ['assigned', 'cancelled'],
        'picked_up' => ['in_transit', 'delivery_failed'],
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

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
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

    public function city()
    {
        return $this->belongsTo(OperationCity::class, 'city_id');
    }

    public function area()
    {
        return $this->belongsTo(OperationArea::class, 'area_id');
    }

    public function dispatchTimeSlot()
    {
        return $this->belongsTo(DispatchTimeSlot::class, 'dispatch_time_slot_id');
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

    public function dispatchRider()
    {
        return $this->belongsTo(DispatchRider::class, 'dispatch_rider_id');
    }

    public function dispatchAssignments()
    {
        return $this->hasMany(DispatchAssignment::class);
    }

    public function currentDispatchAssignment()
    {
        return $this->hasOne(DispatchAssignment::class)->latestOfMany();
    }

    public function canTransitionDeliveryStatusTo(string $nextStatus): bool
    {
        $current = $this->delivery_status ?: 'pending_assignment';
        return in_array($nextStatus, self::DELIVERY_STATUS_TRANSITIONS[$current] ?? [], true);
    }
}
