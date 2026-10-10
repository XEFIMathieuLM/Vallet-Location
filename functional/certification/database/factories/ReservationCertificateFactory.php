<?php

namespace Functional\Certification\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationCertificate>
 */
class ReservationCertificateFactory extends Factory
{
    protected $model = ReservationCertificate::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'status' => CertificateStatus::Pending,
            'attempts' => 0,
            'status_changed_at' => CarbonImmutable::now(),
        ];
    }

    public function withStatus(CertificateStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'delivered_at' => $status->isDelivered() ? CarbonImmutable::now() : null,
        ]);
    }

    public function failed(DispatchFailureReason $failureReason = DispatchFailureReason::InvalidAddress): static
    {
        return $this->withStatus(CertificateStatus::Failed)->state(fn (): array => [
            'attempts' => 1,
            'last_failure_reason' => $failureReason,
        ]);
    }
}
