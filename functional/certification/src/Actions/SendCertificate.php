<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Certificates\CertificateLifecycle;
use Functional\Certification\Dispatches\CertificateMailer;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Events\CertificateChanged;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\ReportInForce;
use Illuminate\Support\Facades\DB;

final class SendCertificate
{
    public function __construct(
        private readonly ReportInForce $reportInForce,
        private readonly CertificateMailer $certificateMailer,
        private readonly CertificateLifecycle $certificateLifecycle,
    ) {}

    public function handle(int $certificateId): void
    {
        DB::transaction(function () use ($certificateId): void {
            $certificate = ReservationCertificate::query()->lockForUpdate()->find($certificateId);

            if ($certificate === null || ! $this->isDue($certificate)) {
                return;
            }

            $report = $this->reportInForce->for($certificate->reservation->machine);
            $recipientEmail = $certificate->reservation->customer->email;

            if ($report === null || $recipientEmail === null) {
                return;
            }

            $failureReason = $this->certificateMailer->send($certificate, $report, $recipientEmail);
            $this->applyOutcome($certificate, $failureReason);
        });
    }

    private function isDue(ReservationCertificate $certificate): bool
    {
        return $certificate->status === CertificateStatus::Pending
            && ($certificate->next_attempt_at === null || $certificate->next_attempt_at->lte(CarbonImmutable::now()))
            && $certificate->reservation->status === ReservationStatus::Confirmed
            && $certificate->reservation->machine->is_subject_to_vgp;
    }

    private function applyOutcome(ReservationCertificate $certificate, ?DispatchFailureReason $failureReason): void
    {
        $attempts = $certificate->attempts + 1;

        if ($failureReason === null) {
            $this->certificateLifecycle->moveTo($certificate, $certificate->state()->send(), ['attempts' => $attempts, 'next_attempt_at' => null, 'last_failure_reason' => null]);

            return;
        }

        if ($failureReason->isPermanent()) {
            $this->certificateLifecycle->moveTo($certificate, $certificate->state()->fail(), ['attempts' => $attempts, 'next_attempt_at' => null, 'last_failure_reason' => $failureReason]);

            return;
        }

        $certificate->update(['attempts' => $attempts, 'next_attempt_at' => $this->nextAttemptAfter($attempts), 'last_failure_reason' => $failureReason]);
        CertificateChanged::dispatch($certificate);
    }

    private function nextAttemptAfter(int $attempts): CarbonImmutable
    {
        $retryDelays = array_values(config()->array('certification.retry_delays_minutes'));
        $delayMinutes = (int) $retryDelays[min($attempts, count($retryDelays)) - 1];

        return CarbonImmutable::now()->addMinutes($delayMinutes);
    }
}
