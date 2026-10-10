<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Certificates\CertificateLifecycle;
use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Exceptions\HandDeliveryRefusedException;
use Functional\Certification\History\CertificationHistory;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Queries\ReportInForce;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class RecordHandDelivery
{
    public function __construct(
        private readonly ReportInForce $reportInForce,
        private readonly CertificateLifecycle $certificateLifecycle,
        private readonly CertificationHistory $certificationHistory,
    ) {}

    public function handle(Reservation $reservation, Authenticatable&AgencyMember $author): ReservationCertificate
    {
        return DB::transaction(function () use ($reservation, $author): ReservationCertificate {
            $certificate = ReservationCertificate::query()->whereBelongsTo($reservation)->lockForUpdate()->first() ?? throw HandDeliveryRefusedException::noCertificate();
            $report = $this->ensureDeliverable($certificate);

            CertificateDispatch::query()->create([
                'reservation_certificate_id' => $certificate->id,
                'vgp_report_id' => $report->id,
                'channel' => DispatchChannel::Hand,
                'is_automatic' => false,
                'author_id' => $author->getAuthIdentifier(),
                'outcome' => DispatchOutcome::Sent,
                'attempted_at' => CarbonImmutable::now(),
            ]);
            $this->certificateLifecycle->moveTo($certificate, $certificate->state()->handDeliver(), ['next_attempt_at' => null]);
            $this->certificationHistory->record($certificate->reservation, CertificationHistoryEvent::CertificateHandDelivered, ['report_id' => $report->id]);

            return $certificate;
        });
    }

    private function ensureDeliverable(ReservationCertificate $certificate): VgpReport
    {
        if ($certificate->reservation->status !== ReservationStatus::Confirmed) {
            throw HandDeliveryRefusedException::reservationNotConfirmed();
        }

        if ($certificate->status->isDelivered()) {
            throw HandDeliveryRefusedException::alreadyDelivered();
        }

        return $this->reportInForce->for($certificate->reservation->machine) ?? throw HandDeliveryRefusedException::noReport();
    }
}
