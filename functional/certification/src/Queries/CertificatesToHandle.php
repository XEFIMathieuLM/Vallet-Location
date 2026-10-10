<?php

namespace Functional\Certification\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Database\Eloquent\Builder;

final class CertificatesToHandle
{
    /**
     * @return Builder<ReservationCertificate>
     */
    public function query(): Builder
    {
        $staleSince = CarbonImmutable::now()->subMinutes(config()->integer('certification.alert_after_minutes'));

        return ReservationCertificate::query()
            ->select('reservation_certificates.*')
            ->join('reservations', 'reservations.id', '=', 'reservation_certificates.reservation_id')
            ->where('reservations.status', ReservationStatus::Confirmed)
            ->where(fn (Builder $toHandle): Builder => $toHandle
                ->whereIn('reservation_certificates.status', [CertificateStatus::Failed, CertificateStatus::AwaitingEmail, CertificateStatus::AwaitingReport])
                ->orWhere(fn (Builder $stalePending): Builder => $stalePending
                    ->where('reservation_certificates.status', CertificateStatus::Pending)
                    ->where('reservation_certificates.status_changed_at', '<=', $staleSince)))
            ->orderBy('reservations.start_date')
            ->orderBy('reservation_certificates.id');
    }
}
