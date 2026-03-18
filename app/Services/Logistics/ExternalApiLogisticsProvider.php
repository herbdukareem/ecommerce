<?php

namespace App\Services\Logistics;

use App\Models\Order;

class ExternalApiLogisticsProvider implements LogisticsProviderInterface
{
    public function __construct(private readonly string $providerKey)
    {
    }

    public function key(): string
    {
        return $this->providerKey;
    }

    public function canDispatch(Order $order): bool
    {
        $config = $this->credentials();

        return !empty($config['api_key']) && !empty($config['api_secret']);
    }

    public function dispatch(Order $order, array $payload = []): array
    {
        if (!$this->canDispatch($order)) {
            return [
                'provider' => $this->key(),
                'status' => 'unavailable',
                'message' => 'Provider credentials are missing or incomplete.',
            ];
        }

        $trackingCode = (string) ($payload['tracking_code'] ?? $order->delivery_tracking_code ?? ('EXT-' . $order->id . '-' . now()->format('YmdHis')));

        return [
            'provider' => $this->key(),
            'status' => 'created',
            'tracking_code' => $trackingCode,
            'message' => strtoupper($this->key()) . ' dispatch request accepted and queued.',
            'meta' => [
                'provider' => $this->key(),
                'order_id' => $order->id,
                'queued_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Resolve provider credentials from config/services.php.
     *
     * @return array{api_key:?string,api_secret:?string}
     */
    protected function credentials(): array
    {
        return [
            'api_key' => (string) config("services.{$this->providerKey}.api_key"),
            'api_secret' => (string) config("services.{$this->providerKey}.api_secret"),
        ];
    }
}
