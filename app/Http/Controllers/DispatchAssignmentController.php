<?php

namespace App\Http\Controllers;

use App\Models\DispatchAssignment;
use App\Services\OrderStatusEmailService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DispatchAssignmentController extends Controller
{
    public function myAssignments(Request $request)
    {
        $rider = $request->user()->dispatchRider;
        abort_unless($rider, 403, 'Dispatch rider profile required.');

        return response()->json([
            'assignments' => DispatchAssignment::query()
                ->with(['order.items.sku.product', 'order.user', 'deliveryPartner'])
                ->where('dispatch_rider_id', $rider->id)
                ->latest()
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function update(Request $request, int $assignmentId)
    {
        $rider = $request->user()->dispatchRider;
        abort_unless($rider, 403, 'Dispatch rider profile required.');

        $assignment = DispatchAssignment::query()
            ->with('order')
            ->where('dispatch_rider_id', $rider->id)
            ->findOrFail($assignmentId);

        $data = $request->validate([
            'status' => 'required|string|in:accepted,rejected,picked_up,in_transit,delivered,failed',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
            'issue_note' => 'required_if:status,failed|nullable|string|max:1000',
        ]);

        $nextStatus = $data['status'];
        if (!$assignment->canTransitionTo($nextStatus)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid dispatch assignment status transition.'],
            ]);
        }
        $oldDeliveryStatus = $assignment->order->delivery_status;

        $timestampColumn = match ($nextStatus) {
            'accepted' => 'accepted_at',
            'rejected' => 'rejected_at',
            'picked_up' => 'picked_up_at',
            'in_transit' => 'in_transit_at',
            'delivered' => 'delivered_at',
            'failed' => 'failed_at',
            default => null,
        };

        $payload = [
            'status' => $nextStatus,
            'rejection_reason' => $data['rejection_reason'] ?? $assignment->rejection_reason,
            'issue_note' => $data['issue_note'] ?? $assignment->issue_note,
        ];

        if ($timestampColumn) {
            $payload[$timestampColumn] = now();
        }

        $assignment->update($payload);

        $orderStatus = match ($nextStatus) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            'picked_up' => 'picked_up',
            'in_transit' => 'in_transit',
            'delivered' => 'delivered',
            'failed' => 'delivery_failed',
            default => $assignment->order->delivery_status,
        };

        $assignment->order->update([
            'delivery_status' => $orderStatus,
            'delivered_at' => $nextStatus === 'delivered' ? now() : $assignment->order->delivered_at,
            'dispatch_note' => $data['issue_note'] ?? $data['rejection_reason'] ?? $assignment->order->dispatch_note,
        ]);

        app(OrderStatusEmailService::class)->notify($assignment->order->fresh(['user', 'items.sku.product', 'deliveryPartner', 'dispatchRider.user']), [[
            'type' => 'Delivery status',
            'old' => $oldDeliveryStatus,
            'new' => $orderStatus,
        ]], $data['issue_note'] ?? $data['rejection_reason'] ?? null);

        if (in_array($nextStatus, ['accepted', 'picked_up', 'in_transit'], true)) {
            $rider->update(['availability_status' => 'on_delivery']);
        }

        if (in_array($nextStatus, ['delivered', 'failed', 'rejected'], true)) {
            $rider->update(['availability_status' => 'available']);
        }

        return response()->json([
            'message' => 'Dispatch assignment updated',
            'assignment' => $assignment->fresh(['order', 'rider.user', 'deliveryPartner']),
        ]);
    }
}
