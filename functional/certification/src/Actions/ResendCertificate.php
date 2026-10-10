<?php

namespace Functional\Certification\Actions;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Certificates\CertificateLifecycle;
use Functional\Certification\Dispatches\CertificateMailer;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Exceptions\CertificateNotResendableException;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\ReportInForce;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class ResendCertificate
{
    public function __construct(
        private readonly ReportInForce $reportInForce,
        private readonly CertificateMailer $certificateMailer,
        private readonly CertificateLifecycle $certificateLifecycle,
    ) {}

    public function handle(Reservation $reservation, Authenticatable&AgencyMember $author): void
    {
        $failureReason = DB::transaction(function () use ($reservation, $author): ?DispatchFailureReason {
            $this->ensureActive($reservation);
            $certificate = ReservationCertificate::query()->whereBelongsTo($reservation)->lockForUpdate()->first() ?? throw CertificateNotResendableException::noCertificate();
            $report = $this->reportInForce->for($reservation->machine) ?? throw CertificateNotResendableException::noReport();
            $recipientEmail = $reservation->customer->email ?? throw CertificateNotResendableException::noEmail();

            $authorId = $author->getAuthIdentifier();
            $failureReason = $this->certificateMailer->send($certificate, $report, $recipientEmail, is_int($authorId) ? $authorId : (int) $authorId);

            if ($failureReason === null && ! $certificate->status->isDelivered()) {
                $this->certificateLifecycle->moveTo($certificate, $certificate->state()->send(), ['next_attempt_at' => null, 'last_failure_reason' => null]);
            }

            return $failureReason;
        });

        if ($failureReason !== null) {
            throw CertificateNotResendableException::sendingFailed($failureReason);
        }
    }

    private function ensureActive(Reservation $reservation): void
    {
        if (! in_array($reservation->status, [ReservationStatus::Confirmed, ReservationStatus::InProgress], true)) {
            throw CertificateNotResendableException::reservationNotActive();
        }
    }
}
