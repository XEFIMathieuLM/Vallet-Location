<?php

namespace Functional\Portal\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetCustomerPasswordMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $accountName,
        public readonly string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('portal::mail.reset.subject'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'portal::mail.reset-password', with: [
            'accountName' => $this->accountName,
            'resetUrl' => $this->resetUrl,
            'lifetimeMinutes' => config()->integer('auth.passwords.customer_accounts.expire'),
        ]);
    }
}
