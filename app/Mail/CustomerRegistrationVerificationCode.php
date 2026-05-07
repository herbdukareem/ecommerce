<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerRegistrationVerificationCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $code,
        public int $expiresInMinutes = 15
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your verification code for ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-registration-verification-code',
            with: [
                'name' => $this->name,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
