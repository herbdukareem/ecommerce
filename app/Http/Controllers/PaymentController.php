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

        $payload = $this->paymentService->initialize($order, $payment);

        return response()->json([
            'message' => 'Payment initialized',
            'payment' => $payment->fresh(),
            'checkout' => $payload,
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
