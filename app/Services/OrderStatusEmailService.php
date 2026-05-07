<?php

namespace App\Services;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderStatusEmailService
{
    public function notify(Order $order, array $changes, ?string $note = null): void
    {
        $changes = collect($changes)
            ->filter(fn ($change) => ($change['old'] ?? null) !== ($change['new'] ?? null))
            ->values()
            ->all();

        if (empty($changes)) {
            return;
        }

        $order->loadMissing('user');
        if (!$order->user?->email) {
            return;
        }

        Mail::to($order->user->email)->queue(new OrderStatusUpdated($order, $changes, $note));
    }
}
