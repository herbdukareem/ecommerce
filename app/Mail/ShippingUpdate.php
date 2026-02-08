<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderFulfillment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShippingUpdate extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public OrderFulfillment $fulfillment
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Shipping Update - Order #' . $this->order->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.shipping-update',
            with: [
                'order' => $this->order,
                'fulfillment' => $this->fulfillment,
                'trackingUrl' => $this->getTrackingUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate tracking URL based on provider
     */
    private function getTrackingUrl(): ?string
    {
        if (!$this->fulfillment->tracking_no) {
            return null;
        }

        $trackingNo = $this->fulfillment->tracking_no;
        
        return match(strtolower($this->fulfillment->shipment_provider)) {
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$trackingNo}",
            'ups' => "https://www.ups.com/track?tracknum={$trackingNo}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$trackingNo}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$trackingNo}",
            default => null,
        };
    }
}

