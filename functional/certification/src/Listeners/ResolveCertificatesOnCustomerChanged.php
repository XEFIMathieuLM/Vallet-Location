<?php

namespace Functional\Certification\Listeners;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\CustomerChanged;
use Functional\Certification\Actions\ResolveCertificateReadiness;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Database\Eloquent\Builder;

final class ResolveCertificatesOnCustomerChanged
{
    public function __construct(private readonly ResolveCertificateReadiness $resolveCertificateReadiness) {}

    public function handle(CustomerChanged $customerChanged): void
    {
        if (! in_array('email', $customerChanged->changedAttributes, true)) {
            return;
        }

        $currentEmail = $customerChanged->customer->email;

        ReservationCertificate::query()
            ->whereIn('status', [CertificateStatus::AwaitingEmail, CertificateStatus::Failed])
            ->whereHas('reservation', fn (Builder $reservations): Builder => $reservations
                ->where('customer_id', $customerChanged->customer->id)
                ->where('status', ReservationStatus::Confirmed))
            ->with(['reservation.machine', 'reservation.customer', 'lastDispatch'])
            ->get()
            ->filter(fn (ReservationCertificate $certificate): bool => $certificate->status === CertificateStatus::AwaitingEmail || $certificate->lastDispatch?->recipient_email !== $currentEmail)
            ->each(fn (ReservationCertificate $certificate) => $this->resolveCertificateReadiness->resolve($certificate));
    }
}
