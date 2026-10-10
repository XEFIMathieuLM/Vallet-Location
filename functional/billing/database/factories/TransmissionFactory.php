<?php

namespace Functional\Billing\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\Transmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transmission>
 */
class TransmissionFactory extends Factory
{
    protected $model = Transmission::class;

    public function definition(): array
    {
        return [
            'billable_period_id' => BillablePeriod::factory(),
            'reservation_id' => fn (array $attributes): mixed => BillablePeriod::query()->whereKey($attributes['billable_period_id'])->value('reservation_id'),
            'status' => TransmissionStatus::Pending,
        ];
    }

    public function forSource(BillableLineType $type, int $sourceId): static
    {
        return $this->state(fn (): array => [
            'billable_period_id' => null,
            'reservation_id' => null,
            'source_type' => $type,
            'source_id' => $sourceId,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => TransmissionStatus::Sent,
            'attempts' => 1,
            'sent_at' => CarbonImmutable::now(),
            'last_attempt_at' => CarbonImmutable::now(),
            'external_ref' => faker()->billingSoftwareRef(),
        ]);
    }

    public function failed(TransmissionFailureReason $failureReason = TransmissionFailureReason::Rejected, string $lastError = 'Donnée invalide'): static
    {
        return $this->state(fn (): array => [
            'status' => TransmissionStatus::Failed,
            'attempts' => 1,
            'last_attempt_at' => CarbonImmutable::now(),
            'failure_reason' => $failureReason,
            'last_error' => $lastError,
        ]);
    }

    public function exported(): static
    {
        return $this->state(fn (): array => [
            'status' => TransmissionStatus::Exported,
            'billing_export_id' => BillingExport::factory(),
        ]);
    }
}
