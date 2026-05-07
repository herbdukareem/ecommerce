<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchAssignment extends Model
{
    use HasFactory;

    public const STATUS_TRANSITIONS = [
        'assigned' => ['accepted', 'rejected', 'cancelled'],
        'accepted' => ['picked_up', 'failed'],
        'picked_up' => ['in_transit', 'failed'],
        'in_transit' => ['delivered', 'failed'],
        'rejected' => [],
        'delivered' => [],
        'failed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'order_id',
        'dispatch_rider_id',
        'delivery_partner_id',
        'assigned_by',
        'previous_dispatch_rider_id',
        'status',
        'rejection_reason',
        'issue_note',
        'assigned_at',
        'accepted_at',
        'rejected_at',
        'picked_up_at',
        'in_transit_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(DispatchRider::class, 'dispatch_rider_id');
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function previousRider()
    {
        return $this->belongsTo(DispatchRider::class, 'previous_dispatch_rider_id');
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        return in_array($nextStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }
}
