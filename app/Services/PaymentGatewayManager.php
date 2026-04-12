<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
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
            ->map(fn (PaymentGateway $gateway) => $this->adminPayload($gateway))
            ->values();
    }

    public function adminPayload(PaymentGateway $gateway): array
    {
        return array_merge($gateway->toArray(), [
            'readiness' => $this->readiness($gateway->provider, $gateway->mode),
            'extra_config' => $this->adminSafeExtraConfig($gateway),
        ]);
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
                $runtime = $this->providerRuntimeConfig($gateway->provider, $gateway->mode);

                return [
                    'provider' => $gateway->provider,
                    'display_name' => $gateway->display_name,
                    'description' => $gateway->description,
                    'sort_order' => $gateway->sort_order,
                    'mode' => $gateway->mode,
                    'supported_currencies' => $gateway->supported_currencies ?? ['NGN'],
                    'fee_type' => $gateway->fee_type,
                    'fee_value' => (float) $gateway->fee_value,
                    'extra_config' => [
                        'public_key' => $runtime['public_key'] ?? null,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function readiness(string $provider, string $mode = 'sandbox', ?array $extraConfigOverride = null): array
    {
        $missing = [];
        $runtime = $this->providerRuntimeConfig($provider, $mode, $extraConfigOverride);

        if ($provider === 'paystack') {
            if (empty($runtime['public_key'])) {
                $missing[] = 'Paystack public key (' . $mode . ')';
            }
            if (empty($runtime['secret_key'])) {
                $missing[] = 'Paystack secret key (' . $mode . ')';
            }
        }

        if ($provider === 'flutterwave') {
            if (empty($runtime['secret_key'])) {
                $missing[] = 'Flutterwave secret key (' . $mode . ')';
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

    public function providerRuntimeConfig(string $provider, ?string $mode = null, ?array $extraConfigOverride = null): array
    {
        $gateway = PaymentGateway::query()->where('provider', $provider)->first();
        $activeMode = $mode ?: ($gateway?->mode ?: 'sandbox');
        $extraConfig = $extraConfigOverride ?? ($gateway?->extra_config ?? []);

        $credentials = Arr::get($extraConfig, 'credentials.' . $activeMode, []);
        $legacy = Arr::except($extraConfig, ['credentials']);
        $raw = array_merge($legacy, $credentials);

        $raw['secret_key'] = $this->tryDecryptValue($raw['secret_key'] ?? null);
        $raw['webhook_secret'] = $this->tryDecryptValue($raw['webhook_secret'] ?? null);

        if ($provider === 'paystack') {
            return [
                'mode' => $activeMode,
                'secret_key' => $raw['secret_key'] ?: config('services.paystack.secret_key'),
                'public_key' => $raw['public_key'] ?? config('services.paystack.public_key'),
                'webhook_secret' => $raw['webhook_secret'] ?: config('services.paystack.webhook_secret') ?: ($raw['secret_key'] ?: config('services.paystack.secret_key')),
                'base_url' => $raw['base_url'] ?? config('services.paystack.base_url', 'https://api.paystack.co'),
                'callback_url' => $raw['callback_url'] ?? config('services.paystack.callback_url') ?: config('app.url') . '/dashboard',
            ];
        }

        if ($provider === 'flutterwave') {
            return [
                'mode' => $activeMode,
                'secret_key' => $raw['secret_key'] ?: config('services.flutterwave.secret_key'),
                'public_key' => $raw['public_key'] ?? config('services.flutterwave.public_key'),
                'webhook_secret' => $raw['webhook_secret'] ?: config('services.flutterwave.webhook_secret'),
                'base_url' => $raw['base_url'] ?? config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'),
                'redirect_url' => $raw['redirect_url'] ?? config('services.flutterwave.redirect_url') ?: config('app.url') . '/dashboard',
                'currency' => $raw['currency'] ?? config('services.flutterwave.currency', 'NGN'),
            ];
        }

        return ['mode' => $activeMode];
    }

    public function normalizeExtraConfigForStorage(string $provider, array $incoming, ?PaymentGateway $gateway = null): array
    {
        $existing = $gateway?->extra_config ?? [];

        if ($provider === 'paystack' || $provider === 'flutterwave') {
            $modes = ['sandbox', 'live'];
            $normalizedCredentials = Arr::get($existing, 'credentials', []);

            foreach ($modes as $mode) {
                $existingMode = Arr::get($existing, 'credentials.' . $mode, []);
                $incomingMode = Arr::get($incoming, 'credentials.' . $mode, []);

                $normalizedCredentials[$mode] = $this->mergeModeCredentials($incomingMode, $existingMode);
            }

            return array_merge($existing, Arr::except($incoming, ['credentials']), [
                'credentials' => $normalizedCredentials,
            ]);
        }

        return array_merge($existing, $incoming);
    }

    protected function mergeModeCredentials(array $incomingMode, array $existingMode): array
    {
        $incoming = Arr::except($incomingMode, ['has_secret_key', 'has_webhook_secret']);
        $existing = Arr::except($existingMode, ['has_secret_key', 'has_webhook_secret']);

        $knownKeys = [
            'public_key',
            'secret_key',
            'webhook_secret',
            'base_url',
            'callback_url',
            'redirect_url',
            'currency',
            'encryption_key',
            'api_secret',
            'private_key',
            'token',
        ];

        $keys = array_values(array_unique(array_merge($knownKeys, array_keys($existing), array_keys($incoming))));
        $merged = [];

        foreach ($keys as $key) {
            $incomingHasKey = array_key_exists($key, $incoming);
            $incomingValue = $incoming[$key] ?? null;
            $existingValue = $existing[$key] ?? null;

            if ($this->isSensitiveCredentialKey($key)) {
                $merged[$key] = $this->mergeSensitiveCredential($incomingHasKey, $incomingValue, $existingValue);
                continue;
            }

            $merged[$key] = $this->mergeOptionalStringPreservingExisting($incomingHasKey, $incomingValue, $existingValue);
        }

        return $merged;
    }

    protected function isSensitiveCredentialKey(string $key): bool
    {
        $needle = strtolower($key);
        return str_contains($needle, 'secret') || str_contains($needle, 'token') || str_contains($needle, 'private') || str_contains($needle, 'encryption');
    }

    protected function mergeSensitiveCredential(bool $incomingHasKey, mixed $incoming, mixed $existing): ?string
    {
        // Field omitted, null, or blank means keep current stored sensitive value.
        if (!$incomingHasKey || !is_string($incoming) || trim($incoming) === '') {
            return is_string($existing) && trim($existing) !== '' ? $existing : null;
        }

        return Crypt::encryptString(trim($incoming));
    }

    protected function mergeOptionalStringPreservingExisting(bool $incomingHasKey, mixed $incoming, mixed $existing): ?string
    {
        if (!$incomingHasKey) {
            return is_string($existing) && trim($existing) !== '' ? $existing : null;
        }

        if (is_string($incoming) && trim($incoming) !== '') {
            return trim($incoming);
        }

        return is_string($existing) && trim($existing) !== '' ? $existing : null;
    }

    protected function adminSafeExtraConfig(PaymentGateway $gateway): array
    {
        $extra = $gateway->extra_config ?? [];
        $credentials = Arr::get($extra, 'credentials', []);

        $safeCredentials = [];
        foreach (['sandbox', 'live'] as $mode) {
            $current = $credentials[$mode] ?? [];
            $safeCredentials[$mode] = [
                'public_key' => $current['public_key'] ?? null,
                'base_url' => $current['base_url'] ?? null,
                'callback_url' => $current['callback_url'] ?? null,
                'redirect_url' => $current['redirect_url'] ?? null,
                'currency' => $current['currency'] ?? null,
                'has_secret_key' => !empty($this->tryDecryptValue($current['secret_key'] ?? null)),
                'has_webhook_secret' => !empty($this->tryDecryptValue($current['webhook_secret'] ?? null)),
            ];
        }

        return array_merge(Arr::except($extra, ['secret_key', 'secret', 'webhook_secret', 'credentials']), [
            'credentials' => $safeCredentials,
        ]);
    }

    protected function mergeOptionalString(mixed $incoming, mixed $existing): ?string
    {
        if (is_string($incoming) && trim($incoming) !== '') {
            return trim($incoming);
        }

        return is_string($existing) && trim($existing) !== '' ? $existing : null;
    }

    protected function mergeEncryptedSecret(mixed $incoming, mixed $existing): ?string
    {
        if (is_string($incoming) && trim($incoming) !== '') {
            return Crypt::encryptString(trim($incoming));
        }

        return is_string($existing) && trim($existing) !== '' ? $existing : null;
    }

    protected function tryDecryptValue(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
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
