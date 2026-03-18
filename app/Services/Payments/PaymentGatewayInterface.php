<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function provider(): string;

    public function initialize(Payment $payment, array $metadata = []): array;

    public function verify(Payment $payment, string $reference): array;

    public function validateWebhookSignature(string $rawPayload, array $headers): bool;

    public function extractReferenceFromWebhook(array $payload): ?string;
}
