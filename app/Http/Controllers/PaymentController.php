<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyPaymentJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentGatewayManager $gatewayManager
    )
    {
    }

    public function gateways()
    {
        return response()->json([
            'gateways' => $this->gatewayManager->checkoutList(),
        ]);
    }

    public function initialize(Request $request, $orderId)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->with('payments')
            ->findOrFail($orderId);

        $payment = $order->payments()->latest()->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment record not found'], 404);
        }

        if (in_array($payment->status, ['paid', 'completed'], true)) {
            return response()->json(['message' => 'Payment already completed'], 422);
        }

        try {
            $payload = $this->paymentService->initialize($order, $payment);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Unable to initialize payment right now.',
            ], 422);
        }

        $provider = (string) ($payload['provider'] ?? data_get($payment->gateway_response, 'provider', 'dummy'));
        $gateway = $provider !== 'dummy'
            ? \App\Models\PaymentGateway::query()->where('provider', $provider)->first()
            : null;
        $mode = $gateway?->mode ?? 'sandbox';
        $runtime = $provider !== 'dummy' ? $this->gatewayManager->providerRuntimeConfig($provider, $mode) : [];

        $flow = $provider === 'paystack' ? 'popup' : 'redirect';

        $checkoutPayload = [
            'provider' => $provider,
            'reference' => $payload['reference'] ?? $payment->transaction_id,
            'checkout_url' => $payload['checkout_url'] ?? null,
            'access_code' => $payload['access_code'] ?? null,
        ];

        if ($provider === 'paystack') {
            $checkoutPayload = array_merge($checkoutPayload, [
                'public_key' => $runtime['public_key'] ?? null,
                'email' => $order->user?->email,
                'amount' => (int) round(((float) $payment->amount) * 100),
                'currency' => 'NGN',
                'metadata' => [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'customer_id' => $order->user_id,
                ],
                'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
            ]);
        }

        return response()->json([
            'message' => 'Payment initialized',
            'success' => true,
            'provider' => $provider,
            'payment_flow' => $flow,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'payment' => $payment->fresh(),
            'checkout' => $checkoutPayload,
        ]);
    }

    public function verify(Request $request, $paymentId)
    {
        $request->validate([
            'reference' => 'required|string',
            'provider' => 'nullable|string|in:dummy,paystack,flutterwave',
        ]);

        $payment = Payment::whereHas('order', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->findOrFail($paymentId);

        $result = $this->paymentService->verify($payment, $request->reference, $request->input('provider'));

        return response()->json([
            'message' => 'Payment verification complete',
            'result' => $result,
            'payment' => $payment->fresh(),
        ]);
    }

    public function webhook(Request $request, string $provider)
    {
        $payload = $request->all();
        $result = $this->paymentService->handleWebhook($provider, $request->getContent(), $request->headers->all(), $payload);

        if (!$result['accepted']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        VerifyPaymentJob::dispatch($result['payment_id'], $result['reference'], $result['provider']);

        return response()->json([
            'message' => 'Webhook accepted for processing',
        ], 202);
    }
}
