<?php

namespace App\Services\Logistics;

use App\Models\Order;

class LogisticsManager
{
    /**
     * @var array<string, LogisticsProviderInterface>
     */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'manual_local_partner' => new ManualLogisticsProvider(),
            'dhl' => new ExternalApiLogisticsProvider('dhl'),
            'gig' => new ExternalApiLogisticsProvider('gig'),
            'kwik' => new ExternalApiLogisticsProvider('kwik'),
            'sendbox' => new ExternalApiLogisticsProvider('sendbox'),
        ];
    }

    public function dispatch(Order $order, string $provider = 'manual_local_partner', array $payload = []): array
    {
        $driver = $this->providers[$provider] ?? $this->providers['manual_local_partner'];

        if (!$driver->canDispatch($order)) {
            return [
                'provider' => $driver->key(),
                'status' => 'unavailable',
                'message' => 'Dispatch provider is not available for this order.',
            ];
        }

        return $driver->dispatch($order, $payload);
    }
}
