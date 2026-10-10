<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Events\CertificateChanged;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Queries\ReportInForce;

final class OpenReservationCertificate
{
    public function __construct(
        private readonly CertificationCalendar $certificationCalendar,
        private readonly ResolveCertificateReadiness $resolveCertificateReadiness,
        private readonly ReportInForce $reportInForce,
    ) {}

    public function handle(Reservation $reservation): ?ReservationCertificate
    {
        if (! $this->certificationCalendar->isLive() || ! $this->isConcerned($reservation)) {
            return null;
        }

        return $this->open($reservation, $this->reportInForce->for($reservation->machine));
    }

    public function handleWithReport(Reservation $reservation, ?VgpReport $reportInForce): ?ReservationCertificate
    {
        if (! $this->certificationCalendar->isLive() || ! $this->isConcerned($reservation)) {
            return null;
        }

        return $this->open($reservation, $reportInForce);
    }

    private function open(Reservation $reservation, ?VgpReport $reportInForce): ReservationCertificate
    {
        $certificate = ReservationCertificate::query()->createOrFirst(
            ['reservation_id' => $reservation->id],
            ['status' => $this->resolveCertificateReadiness->targetStatusGiven($reservation, $reportInForce), 'status_changed_at' => CarbonImmutable::now()],
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
