<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwaveGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected ?string $secret;
    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');
        $this->secret = config('services.flutterwave.secret_key');
        $this->webhookSecret = config('services.flutterwave.webhook_secret');
    }

    public function provider(): string
    {
        return 'flutterwave';
    }

    public function initialize(Payment $payment, array $metadata = []): array
    {
        if (!$this->secret) {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }

        $reference = $metadata['reference'] ?? ('flw_' . $payment->id . '_' . Str::uuid()->toString());
        $email = $metadata['email'] ?? 'customer@example.com';

        $response = Http::withToken($this->secret)
            ->post($this->baseUrl . '/payments', [
                'tx_ref' => $reference,
                'amount' => (float) $payment->amount,
                'currency' => config('services.flutterwave.currency', 'NGN'),
                'redirect_url' => config('services.flutterwave.redirect_url') ?: config('app.url') . '/dashboard',
                'customer' => [
                    'email' => $email,
                    'name' => $metadata['customer_name'] ?? 'Customer',
                ],
                'meta' => $metadata,
                'customizations' => [
                    'title' => config('app.name', 'Ecommerce Payment'),
                ],
            ]);

        if (!$response->successful() || strtolower((string) $response->json('status')) !== 'success') {
            Log::error('flutterwave_initialize_failed', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Unable to initialize Flutterwave transaction.');
        }

        return [
            'provider' => $this->provider(),
            'reference' => $reference,
            'checkout_url' => $response->json('data.link'),
            'raw' => $response->json(),
        ];
    }

    public function verify(Payment $payment, string $reference): array
    {
        if (!$this->secret) {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }

        $response = Http::withToken($this->secret)
            ->get($this->baseUrl . '/transactions/verify_by_reference', [
                'tx_ref' => $reference,
            ]);

        if (!$response->successful()) {
            Log::error('flutterwave_verify_failed', [
                'payment_id' => $payment->id,
                'reference' => $reference,
                'status' => $response->status(),
            ]);
            return [
                'status' => 'failed',
                'reference' => $reference,
                'provider' => $this->provider(),
                'raw' => $response->json(),
            ];
        }

        $gatewayStatus = strtolower((string) $response->json('data.status'));
        $status = match ($gatewayStatus) {
            'successful' => 'paid',
            'failed', 'cancelled' => 'failed',
            default => 'pending',
        };

        return [
            'status' => $status,
            'reference' => $reference,
            'provider' => $this->provider(),
            'raw' => $response->json(),
        ];
    }

    public function validateWebhookSignature(string $rawPayload, array $headers): bool
    {
        if (!$this->webhookSecret) {
            return false;
        }

        $signature = $headers['verif-hash'][0] ?? null;
        if (!$signature) {
            return false;
        }

        return hash_equals($this->webhookSecret, $signature);
    }

    public function extractReferenceFromWebhook(array $payload): ?string
    {
        return $payload['data']['tx_ref'] ?? null;
    }
}
