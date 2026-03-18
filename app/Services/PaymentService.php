<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\DummyGateway;
use App\Services\Payments\FlutterwaveGateway;
use App\Services\Payments\PaystackGateway;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /** @var array<string, PaymentGatewayInterface> */
    protected array $gateways;

    public function __construct(
        protected ?PaymentGatewayInterface $gateway = null,
        protected ?PaymentGatewayManager $gatewayManager = null
    )
    {
        $this->gateways = [
            'dummy' => new DummyGateway(),
            'paystack' => new PaystackGateway(),
            'flutterwave' => new FlutterwaveGateway(),
        ];

        if ($this->gateway) {
            $this->gateways[$this->gateway->provider()] = $this->gateway;
        }
    }

    public function initialize(Order $order, Payment $payment): array
    {
        $gateway = $this->resolveGatewayForPayment($payment);
        $reference = 'ord_' . $order->id . '_pay_' . $payment->id . '_' . Str::uuid()->toString();

        $payload = $gateway->initialize($payment, [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'email' => $order->user?->email,
            'customer_name' => $order->user?->name,
            'reference' => $reference,
        ]);

        $payment->update([
            'transaction_id' => $payload['reference'],
            'gateway_response' => array_merge($payload, ['provider' => $gateway->provider()]),
        ]);

        return $payload;
    }

    public function verify(Payment $payment, string $reference, ?string $provider = null): array
    {
        return DB::transaction(function () use ($payment, $reference, $provider) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);

            if (!$lockedPayment) {
                return [
                    'status' => 'failed',
                    'reference' => $reference,
                    'provider' => $provider ?? 'unknown',
                    'raw' => ['message' => 'Payment not found'],
                ];
            }

            if (in_array($lockedPayment->status, ['paid', 'completed', 'refunded'], true)) {
                return [
                    'status' => $lockedPayment->status === 'completed' ? 'paid' : $lockedPayment->status,
                    'reference' => $lockedPayment->transaction_id ?? $reference,
                    'provider' => $this->determineProvider($lockedPayment, $provider),
                    'raw' => ['message' => 'Payment already processed'],
                    'idempotent' => true,
                ];
            }

            $gateway = $this->resolveGatewayForPayment($lockedPayment, $provider);
            $result = $gateway->verify($lockedPayment, $reference);
            $normalizedStatus = Arr::get($result, 'status', 'failed');
            $paidAt = $normalizedStatus === 'paid' ? ($lockedPayment->paid_at ?? now()) : null;

            $lockedPayment->update([
                'status' => $normalizedStatus,
                'transaction_id' => $reference,
                'gateway_response' => $result,
                'paid_at' => $paidAt,
            ]);

            if ($normalizedStatus === 'paid') {
                $lockedPayment->order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);
            }

            if ($normalizedStatus === 'failed') {
                $lockedPayment->order->update([
                    'payment_status' => 'failed',
                ]);
            }

            return $result;
        });
    }

    public function handleWebhook(string $provider, string $rawPayload, array $headers, array $payload): array
    {
        $gateway = $this->resolveGatewayByProvider($provider);

        if (!$gateway->validateWebhookSignature($rawPayload, $headers)) {
            return ['accepted' => false, 'status' => 401, 'message' => 'Invalid webhook signature'];
        }

        $reference = $gateway->extractReferenceFromWebhook($payload);
        if (!$reference) {
            return ['accepted' => false, 'status' => 422, 'message' => 'Missing payment reference'];
        }

        $payment = Payment::where('transaction_id', $reference)->latest('id')->first();
        if (!$payment) {
            return ['accepted' => false, 'status' => 404, 'message' => 'Payment not found'];
        }

        return [
            'accepted' => true,
            'status' => 202,
            'payment_id' => $payment->id,
            'reference' => $reference,
            'provider' => $provider,
        ];
    }

    protected function resolveGatewayForPayment(Payment $payment, ?string $provider = null): PaymentGatewayInterface
    {
        $provider = $provider ?: $this->determineProvider($payment);
        return $this->resolveGatewayByProvider($provider);
    }

    protected function determineProvider(Payment $payment, ?string $fallbackProvider = null): string
    {
        $storedProvider = data_get($payment->gateway_response, 'provider');
        if ($storedProvider && isset($this->gateways[$storedProvider])) {
            return $storedProvider;
        }

        if ($this->gatewayManager) {
            try {
                return $this->gatewayManager->resolveProviderForCheckout($fallbackProvider);
            } catch (\Throwable $e) {
                // Fall through to config-based fallback for historical/backward compatibility.
            }
        }

        if ($fallbackProvider && isset($this->gateways[$fallbackProvider])) {
            return $fallbackProvider;
        }

        $methodMap = (array) config('payments.method_map', []);
        $methodProvider = $payment->method ? ($methodMap[$payment->method] ?? null) : null;
        if ($methodProvider && isset($this->gateways[$methodProvider])) {
            return $methodProvider;
        }

        $defaultProvider = (string) config('payments.default_gateway', 'dummy');
        return isset($this->gateways[$defaultProvider]) ? $defaultProvider : 'dummy';
    }

    protected function resolveGatewayByProvider(string $provider): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$provider])) {
            return $this->gateways['dummy'];
        }

        if ($provider === 'paystack' && !config('services.paystack.secret_key')) {
            return $this->gateways['dummy'];
        }

        if ($provider === 'flutterwave' && !config('services.flutterwave.secret_key')) {
            return $this->gateways['dummy'];
        }

        return $this->gateways[$provider];
    }
}
