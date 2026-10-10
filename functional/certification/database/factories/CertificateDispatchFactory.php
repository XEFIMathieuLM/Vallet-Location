<?php

namespace Functional\Certification\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateDispatch>
 */
class CertificateDispatchFactory extends Factory
{
    protected $model = CertificateDispatch::class;

    public function definition(): array
    {
        return [
            'reservation_certificate_id' => ReservationCertificate::factory(),
            'vgp_report_id' => VgpReport::factory(),
            'channel' => DispatchChannel::Email,
            'recipient_email' => faker()->email(),
            'is_automatic' => true,
            'outcome' => DispatchOutcome::Sent,
            'attempted_at' => CarbonImmutable::now(),
        ];
    }

    public function failed(DispatchFailureReason $failureReason = DispatchFailureReason::InvalidAddress): static
    {
        return $this->state(fn (): array => [
            'outcome' => DispatchOutcome::Failed,
            'failure_reason' => $failureReason,
        ]);
    }
}
