<?php

namespace Functional\Certification\Mail;

use Functional\Booking\Models\Reservation;
use Functional\Certification\Models\VgpReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VgpCertificateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
        public readonly VgpReport $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('certification::mail.subject', [
            'reference' => $this->reservation->machine->reference,
            'start' => $this->reservation->start_date->format('d/m/Y'),
            'end' => $this->reservation->end_date->format('d/m/Y'),
        ]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'certification::mail.vgp-certificate', with: [
            'customerName' => $this->reservation->customer->name,
            'machineReference' => $this->reservation->machine->reference,
            'machineCategory' => $this->reservation->machine->category->name,
            'startDate' => $this->reservation->start_date->format('d/m/Y'),
            'endDate' => $this->reservation->end_date->format('d/m/Y'),
            'agencyName' => $this->reservation->machine->agency->name,
            'verifiedOn' => $this->report->verified_on->format('d/m/Y'),
            'dueOn' => $this->report->due_on->format('d/m/Y'),
        ]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(config()->string('certification.reports_disk'), $this->report->file_path)
                ->as($this->report->attachmentName())
                ->withMime($this->report->mime_type),
        ];
    }
}
