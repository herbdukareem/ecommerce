<?php

namespace App\Services\Payments;

use App\Models\Payment;

class DummyGateway implements PaymentGatewayInterface
{
    public function provider(): string
    {
        return 'dummy';
    }

    public function initialize(Payment $payment, array $metadata = []): array
    {
        $reference = 'pay_' . $payment->id . '_' . time();

        return [
            'reference' => $reference,
            'checkout_url' => config('app.url') . '/dashboard?payment_ref=' . $reference,
            'provider' => 'dummy',
            'metadata' => $metadata,
        ];
    }

    public function verify(Payment $payment, string $reference): array
    {
        $isSuccess = str_starts_with($reference, 'pay_');

        return [
            'status' => $isSuccess ? 'paid' : 'failed',
            'reference' => $reference,
            'provider' => $this->provider(),
            'raw' => [
                'message' => $isSuccess ? 'Payment verified' : 'Payment failed verification',
            ],
        ];
    }

    public function validateWebhookSignature(string $rawPayload, array $headers): bool
    {
        return true;
    }

    public function extractReferenceFromWebhook(array $payload): ?string
    {
        return $payload['reference'] ?? $payload['data']['reference'] ?? null;
    }
}
