<?php

namespace App\Services\Logistics;

use App\Models\Order;

class ManualLogisticsProvider implements LogisticsProviderInterface
{
    public function key(): string
    {
        return 'manual_local_partner';
    }

    public function canDispatch(Order $order): bool
    {
        return $order->deliveryPartner !== null;
    }

    public function dispatch(Order $order, array $payload = []): array
    {
        return [
            'provider' => $this->key(),
            'status' => 'accepted',
            'tracking_code' => $payload['tracking_code'] ?? $order->delivery_tracking_code,
            'message' => 'Delivery assigned to local partner for manual dispatch.',
        ];
    }
}
