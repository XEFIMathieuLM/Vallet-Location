<?php

namespace Functional\Portal\Mail;

use Functional\Portal\Models\ReservationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationRequestExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ReservationRequest $reservationRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('portal::mail.decision.expired.subject', ['reference' => $this->reservationRequest->machine->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'portal::mail.request-expired', with: ReservationRequestMailDetails::of($this->reservationRequest));
    }
}
