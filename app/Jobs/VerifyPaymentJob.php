<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $paymentId, public string $reference, public ?string $provider = null)
    {
    }

    public function handle(PaymentService $paymentService): void
    {
        $payment = Payment::find($this->paymentId);
        if (!$payment) {
            return;
        }

        $paymentService->verify($payment, $this->reference, $this->provider);
    }
}
