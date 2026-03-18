<?php

namespace App\Services\Logistics;

use App\Models\Order;

interface LogisticsProviderInterface
{
    public function key(): string;

    public function canDispatch(Order $order): bool;

    /**
     * @return array<string, mixed>
     */
    public function dispatch(Order $order, array $payload = []): array;
}
