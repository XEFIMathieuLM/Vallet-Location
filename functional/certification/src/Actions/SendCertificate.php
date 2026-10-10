<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Certification\Certificates\CertificateLifecycle;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\History\CertificationHistory;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Queries\ReportInForce;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class SendCertificate
{
    public function __construct(
        private readonly ReportInForce $reportInForce,
        private readonly CertificateLifecycle $certificateLifecycle,
        private readonly CertificationHistory $certificationHistory,
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

            $this->send($certificate, $report, $recipientEmail);
        });
    }

    private function isDue(ReservationCertificate $certificate): bool
    {
        return $certificate->status === CertificateStatus::Pending
            && ($certificate->next_attempt_at === null || $certificate->next_attempt_at->lte(CarbonImmutable::now()))
            && $certificate->reservation->status === ReservationStatus::Confirmed
            && $certificate->reservation->machine->is_subject_to_vgp;
    }

    private function send(ReservationCertificate $certificate, VgpReport $report, string $recipientEmail): void
    {
        Notification::route('mail', $recipientEmail)->notifyNow(new VgpCertificateNotification($certificate, $report));

        CertificateDispatch::query()->create([
            'reservation_certificate_id' => $certificate->id,
            'vgp_report_id' => $report->id,
            'channel' => DispatchChannel::Email,
            'recipient_email' => $recipientEmail,
            'is_automatic' => true,
            'outcome' => DispatchOutcome::Sent,
            'attempted_at' => CarbonImmutable::now(),
        ]);
        $this->certificateLifecycle->moveTo($certificate, $certificate->state()->send(), [
            'attempts' => $certificate->attempts + 1,
            'next_attempt_at' => null,
            'last_failure_reason' => null,
        ]);
        $this->certificationHistory->record($certificate->reservation, CertificationHistoryEvent::CertificateSent, [
            'email' => $recipientEmail,
            'report_id' => $report->id,
            'mode' => __('certification::history.modes.automatic'),
        ]);
    }
}
