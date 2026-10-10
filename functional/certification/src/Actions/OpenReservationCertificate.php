<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Events\CertificateChanged;
use Functional\Certification\Models\ReservationCertificate;

final class OpenReservationCertificate
{
    public function __construct(
        private readonly CertificationCalendar $certificationCalendar,
        private readonly ResolveCertificateReadiness $resolveCertificateReadiness,
    ) {}

    public function handle(Reservation $reservation): ?ReservationCertificate
    {
        if (! $this->certificationCalendar->isLive() || ! $this->isConcerned($reservation)) {
            return null;
        }

        $certificate = ReservationCertificate::query()->createOrFirst(
            ['reservation_id' => $reservation->id],
            ['status' => $this->resolveCertificateReadiness->targetStatusFor($reservation), 'status_changed_at' => CarbonImmutable::now()],
        );

        if ($certificate->wasRecentlyCreated) {
            CertificateChanged::dispatch($certificate);
            $this->resolveCertificateReadiness->queueSendingWhenPending($certificate);
        }

        return $certificate;
    }

    private function isConcerned(Reservation $reservation): bool
    {
        return $reservation->status === ReservationStatus::Confirmed && $reservation->machine->is_subject_to_vgp;
    }
}
