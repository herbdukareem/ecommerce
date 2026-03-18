<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentGatewayManager
{
    public const SUPPORTED_PROVIDERS = ['paystack', 'flutterwave'];

    public function ensureSeededProviders(): void
    {
        $defaults = [
            'paystack' => [
                'display_name' => 'Paystack',
                'description' => 'Paystack card and transfer payments',
                'sort_order' => 1,
            ],
            'flutterwave' => [
                'display_name' => 'Flutterwave',
                'description' => 'Flutterwave multi-channel payments',
                'sort_order' => 2,
            ],
        ];

        foreach (self::SUPPORTED_PROVIDERS as $provider) {
            PaymentGateway::query()->firstOrCreate(
                ['provider' => $provider],
                [
                    'display_name' => $defaults[$provider]['display_name'],
                    'description' => $defaults[$provider]['description'],
                    'is_enabled' => $provider === 'paystack',
                    'is_visible' => true,
                    'is_default' => $provider === 'paystack',
                    'sort_order' => $defaults[$provider]['sort_order'],
                    'mode' => 'sandbox',
                    'supported_currencies' => ['NGN'],
                    'fee_type' => null,
                    'fee_value' => 0,
                    'extra_config' => [],
                ]
            );
        }

        $this->normalizeSingleDefault();
    }

    public function adminList()
    {
        $this->ensureSeededProviders();

        return PaymentGateway::query()
            ->orderBy('sort_order')
            ->orderBy('provider')
            ->get()
            ->map(function (PaymentGateway $gateway) {
                return array_merge($gateway->toArray(), [
                    'readiness' => $this->readiness($gateway->provider, $gateway->mode),
                ]);
            })
            ->values();
    }

    public function checkoutList(): array
    {
        $this->ensureSeededProviders();

        return PaymentGateway::query()
            ->where('is_enabled', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('provider')
            ->get()
            ->filter(function (PaymentGateway $gateway) {
                return $this->readiness($gateway->provider, $gateway->mode)['is_configured'];
            })
            ->map(function (PaymentGateway $gateway) {
                return [
                    'provider' => $gateway->provider,
                    'display_name' => $gateway->display_name,
                    'description' => $gateway->description,
                    'sort_order' => $gateway->sort_order,
                    'mode' => $gateway->mode,
                    'supported_currencies' => $gateway->supported_currencies ?? ['NGN'],
                    'fee_type' => $gateway->fee_type,
                    'fee_value' => (float) $gateway->fee_value,
                    'extra_config' => Arr::except($gateway->extra_config ?? [], ['secret', 'secret_key', 'webhook_secret']),
                ];
            })
            ->values()
            ->all();
    }

    public function readiness(string $provider, string $mode = 'sandbox'): array
    {
        $missing = [];

        if ($provider === 'paystack') {
            if (!config('services.paystack.secret_key')) {
                $missing[] = 'PAYSTACK_SECRET_KEY';
            }
        }

        if ($provider === 'flutterwave') {
            if (!config('services.flutterwave.secret_key')) {
                $missing[] = 'FLUTTERWAVE_SECRET_KEY';
            }
        }

        return [
            'is_configured' => count($missing) === 0,
            'missing_requirements' => $missing,
            'can_activate' => count($missing) === 0,
            'mode' => $mode,
        ];
    }

    public function resolveProviderForCheckout(?string $requestedProvider = null): string
    {
        $this->ensureSeededProviders();

        if ($requestedProvider) {
            $gateway = PaymentGateway::query()->where('provider', $requestedProvider)->first();
            if (!$gateway) {
                throw new \InvalidArgumentException('Selected payment gateway is not supported.');
            }
            if (!$gateway->is_enabled || !$gateway->is_visible) {
                throw new \InvalidArgumentException('Selected payment gateway is not available for checkout.');
            }
            if (!$this->readiness($gateway->provider, $gateway->mode)['is_configured']) {
                throw new \InvalidArgumentException('Selected payment gateway is not configured.');
            }

            return $gateway->provider;
        }

        $default = PaymentGateway::query()->where('is_default', true)->first();
        if ($default && $default->is_enabled && $this->readiness($default->provider, $default->mode)['is_configured']) {
            return $default->provider;
        }

        $fallback = PaymentGateway::query()
            ->where('is_enabled', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->first();

        if ($fallback && $this->readiness($fallback->provider, $fallback->mode)['is_configured']) {
            return $fallback->provider;
        }

        throw new \RuntimeException('No enabled and configured payment gateway is available.');
    }

    public function setDefault(string $provider): PaymentGateway
    {
        $this->ensureSeededProviders();

        return DB::transaction(function () use ($provider) {
            $gateway = PaymentGateway::query()->where('provider', $provider)->firstOrFail();
            $readiness = $this->readiness($gateway->provider, $gateway->mode);

            if (!$gateway->is_enabled || !$readiness['is_configured']) {
                throw new \InvalidArgumentException('Default gateway must be enabled and configured.');
            }

            PaymentGateway::query()->update(['is_default' => false]);
            $gateway->update(['is_default' => true]);

            return $gateway->refresh();
        });
    }

    public function normalizeSingleDefault(): void
    {
        $defaults = PaymentGateway::query()->where('is_default', true)->orderBy('sort_order')->get();

        if ($defaults->count() <= 1) {
            if ($defaults->count() === 0) {
                $candidate = PaymentGateway::query()->orderBy('sort_order')->first();
                if ($candidate) {
                    $candidate->update(['is_default' => true]);
                }
            }
            return;
        }

        $keep = $defaults->first();
        PaymentGateway::query()->where('id', '!=', $keep->id)->update(['is_default' => false]);
    }
}
