<?php

namespace Functional\Certification\Notifications;

use Functional\Certification\Mail\VgpCertificateMail;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

class VgpCertificateNotification extends Notification
{
    public function __construct(
        public readonly ReservationCertificate $certificate,
        public readonly VgpReport $report,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(AnonymousNotifiable $notifiable): Mailable
    {
        $recipientEmail = $notifiable->routeNotificationFor('mail');

        return (new VgpCertificateMail($this->certificate->reservation, $this->report))->to(is_string($recipientEmail) ? $recipientEmail : []);
    }
}
