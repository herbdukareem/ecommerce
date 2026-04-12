<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected ?string $secret;
    protected ?string $webhookSecret;
    protected ?string $callbackUrl;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? config('services.paystack.base_url', 'https://api.paystack.co')), '/');
        $this->secret = $config['secret_key'] ?? config('services.paystack.secret_key');
        $this->webhookSecret = $config['webhook_secret'] ?? config('services.paystack.webhook_secret');
        $this->callbackUrl = $config['callback_url'] ?? config('services.paystack.callback_url') ?: config('app.url') . '/dashboard';
    }

    public function provider(): string
    {
        return 'paystack';
    }

    public function initialize(Payment $payment, array $metadata = []): array
    {
        if (!$this->secret) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $reference = $metadata['reference'] ?? ('pay_' . $payment->id . '_' . Str::uuid()->toString());
        $email = $metadata['email'] ?? 'customer@example.com';

        $response = Http::withToken($this->secret)
            ->post($this->baseUrl . '/transaction/initialize', [
                'email' => $email,
                'amount' => (int) round(((float) $payment->amount) * 100),
                'reference' => $reference,
                'callback_url' => $this->callbackUrl,
                'metadata' => $metadata,
            ]);

        if (!$response->successful() || !$response->json('status')) {
            Log::error('paystack_initialize_failed', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Unable to initialize Paystack transaction.');
        }

        return [
            'provider' => $this->provider(),
            'reference' => $reference,
            'checkout_url' => $response->json('data.authorization_url'),
            'access_code' => $response->json('data.access_code'),
            'raw' => $response->json(),
        ];
    }

    public function verify(Payment $payment, string $reference): array
    {
        if (!$this->secret) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::withToken($this->secret)
            ->get($this->baseUrl . '/transaction/verify/' . urlencode($reference));

        if (!$response->successful()) {
            Log::error('paystack_verify_failed', [
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
            'success' => 'paid',
            'failed', 'abandoned' => 'failed',
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
        if (!$this->secret) {
            return false;
        }

        $signature = $headers['x-paystack-signature'][0] ?? null;
        if (!$signature) {
            return false;
        }

        $key = $this->webhookSecret ?: $this->secret;
        if (!$key) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawPayload, $key);
        return hash_equals($expected, $signature);
    }

    public function extractReferenceFromWebhook(array $payload): ?string
    {
        return $payload['data']['reference'] ?? null;
    }
}
