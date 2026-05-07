<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\CurrencyFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public array $changes,
        public ?string $note = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order #' . $this->order->id . ' update: ' . $this->primaryStatusLabel(),
        );
    }

    public function content(): Content
    {
        $order = $this->order->loadMissing([
            'user',
            'items.sku.product',
            'items.productOption',
            'deliveryPartner',
            'dispatchRider.user',
            'city',
            'area',
            'dispatchTimeSlot',
        ]);

        return new Content(
            view: 'emails.order-status-updated',
            with: [
                'order' => $order,
                'items' => $order->items,
                'changes' => $this->changes,
                'note' => $this->note,
                'currency' => app(CurrencyFormatter::class),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    protected function primaryStatusLabel(): string
    {
        $lastChange = collect($this->changes)->last();

        return $this->humanize((string) ($lastChange['new'] ?? 'updated'));
    }

    protected function humanize(string $value): string
    {
        return str($value)->replace('_', ' ')->title()->toString();
    }
}
