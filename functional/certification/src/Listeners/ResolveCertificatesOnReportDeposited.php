<?php

namespace Functional\Certification\Listeners;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Actions\ResolveCertificateReadiness;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Events\VgpReportDeposited;
use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Database\Eloquent\Builder;

final class ResolveCertificatesOnReportDeposited
{
    public function __construct(private readonly ResolveCertificateReadiness $resolveCertificateReadiness) {}

    public function handle(VgpReportDeposited $vgpReportDeposited): void
    {
        ReservationCertificate::query()
            ->where('status', CertificateStatus::AwaitingReport)
            ->whereHas('reservation', fn (Builder $reservations): Builder => $reservations
                ->where('machine_id', $vgpReportDeposited->report->machine_id)
                ->where('status', ReservationStatus::Confirmed))
            ->with(['reservation.machine', 'reservation.customer'])
            ->get()
            ->each(fn (ReservationCertificate $certificate) => $this->resolveCertificateReadiness->resolve($certificate, $vgpReportDeposited->report));
    }
}
