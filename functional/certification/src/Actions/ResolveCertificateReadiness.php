<?php

namespace Functional\Certification\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Certification\Certificates\CertificateLifecycle;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Jobs\SendCertificateJob;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\ReportInForce;

final class ResolveCertificateReadiness
{
    public function __construct(
        private readonly ReportInForce $reportInForce,
        private readonly CertificateLifecycle $certificateLifecycle,
    ) {}

    public function targetStatusFor(Reservation $reservation): CertificateStatus
    {
        if ($this->reportInForce->for($reservation->machine) === null) {
            return CertificateStatus::AwaitingReport;
        }

        if ($reservation->customer->email === null) {
            return CertificateStatus::AwaitingEmail;
        }

        return CertificateStatus::Pending;
    }

    public function resolve(ReservationCertificate $certificate): void
    {
        $targetStatus = $this->targetStatusFor($certificate->reservation);
        $currentState = $certificate->state();

        $nextState = match (true) {
            $targetStatus === $certificate->status => null,
            $targetStatus === CertificateStatus::Pending && in_array($certificate->status, [CertificateStatus::AwaitingReport, CertificateStatus::AwaitingEmail, CertificateStatus::Failed], true) => $currentState->queue(),
            $targetStatus === CertificateStatus::AwaitingEmail && $certificate->status === CertificateStatus::AwaitingReport => $currentState->awaitEmail(),
            default => null,
        };

        if ($nextState === null) {
            return;
        }

        $this->certificateLifecycle->moveTo($certificate, $nextState, ['attempts' => 0, 'next_attempt_at' => null, 'last_failure_reason' => null]);
        $this->queueSendingWhenPending($certificate);
    }

    public function queueSendingWhenPending(ReservationCertificate $certificate): void
    {
        if ($certificate->status === CertificateStatus::Pending) {
            SendCertificateJob::dispatch($certificate->id);
        }
    }
}
