<?php

namespace Functional\Certification\Dispatches;

use Carbon\CarbonImmutable;
use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\History\CertificationHistory;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class CertificateMailer
{
    public function __construct(
        private readonly DispatchFailureClassifier $dispatchFailureClassifier,
        private readonly CertificationHistory $certificationHistory,
    ) {}

    public function send(ReservationCertificate $certificate, VgpReport $report, string $recipientEmail, ?int $authorId = null): ?DispatchFailureReason
    {
        $failureReason = rescue(
            function () use ($certificate, $report, $recipientEmail): ?DispatchFailureReason {
                Notification::route('mail', $recipientEmail)->notifyNow(new VgpCertificateNotification($certificate, $report));

                return null;
            },
            fn (Throwable $failure): DispatchFailureReason => $this->dispatchFailureClassifier->classify($failure) ?? throw $failure,
            report: false,
        );

        $this->record($certificate, $report, $recipientEmail, $authorId, $failureReason);

        return $failureReason;
    }

    private function record(ReservationCertificate $certificate, VgpReport $report, string $recipientEmail, ?int $authorId, ?DispatchFailureReason $failureReason): void
    {
        CertificateDispatch::query()->create([
            'reservation_certificate_id' => $certificate->id,
            'vgp_report_id' => $report->id,
            'channel' => DispatchChannel::Email,
            'recipient_email' => $recipientEmail,
            'is_automatic' => $authorId === null,
            'author_id' => $authorId,
            'outcome' => $failureReason === null ? DispatchOutcome::Sent : DispatchOutcome::Failed,
            'failure_reason' => $failureReason,
            'attempted_at' => CarbonImmutable::now(),
        ]);

        $mode = (string) __($authorId === null ? 'certification::history.modes.automatic' : 'certification::history.modes.manual');
        $this->certificationHistory->record(
            $certificate->reservation,
            $failureReason === null ? CertificationHistoryEvent::CertificateSent : CertificationHistoryEvent::CertificateFailed,
            ['email' => $recipientEmail, 'report_id' => $report->id, 'mode' => $mode, 'reason' => $failureReason?->label()],
        );
    }
}
