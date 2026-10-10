<?php

namespace Functional\Certification\Guards;

use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Exceptions\CertificateNotDeliveredException;
use Functional\Certification\Models\ReservationCertificate;

final class CertificateDeliveredGuard implements ReservationTransitionGuard
{
    public function __construct(private readonly CertificationCalendar $certificationCalendar) {}

    public function beforeDeparture(Reservation $reservation): void
    {
        if (! $this->certificationCalendar->isLive() || ! $reservation->machine->is_subject_to_vgp) {
            return;
        }

        $certificate = ReservationCertificate::query()->whereBelongsTo($reservation)->lockForUpdate()->first();

        if ($certificate === null) {
            throw CertificateNotDeliveredException::notOpenedYet();
        }

        if (! $certificate->status->isDelivered()) {
            throw CertificateNotDeliveredException::because($certificate->status, $certificate->last_failure_reason);
        }
    }

    public function beforeReturn(Reservation $reservation): void {}
}
