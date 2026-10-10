<?php

namespace Functional\Portal\Mail;

use Functional\Portal\Notifications\VerifyCustomerEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerifyCustomerEmailMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $accountName,
        public readonly string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('portal::mail.verify.subject'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'portal::mail.verify-email', with: [
            'accountName' => $this->accountName,
            'verificationUrl' => $this->verificationUrl,
            'lifetimeMinutes' => VerifyCustomerEmail::LINK_LIFETIME_MINUTES,
        ]);
    }
}
