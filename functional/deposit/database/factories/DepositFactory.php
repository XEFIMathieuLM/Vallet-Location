<?php

namespace Functional\Deposit\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'amount' => Money::fromStored(150000),
            'payment_method' => PaymentMethod::Cheque,
            'payment_reference' => (string) faker()->number(1000000, 9999999),
            'status' => DepositStatus::Collected,
            'collected_by' => fn () => Factory::factoryForModel($this->userModel()),
            'collected_agency_id' => Agency::factory(),
            'collected_at' => CarbonImmutable::now(),
        ];
    }

    public function withStatus(DepositStatus $status): static
    {
        return match ($status) {
            DepositStatus::Refunded => $this->closed($status, retainedCents: 0),
            DepositStatus::Settled => $this->closed($status, retainedCents: 50000),
            DepositStatus::Collected => $this->state(fn (): array => ['status' => $status]),
            default => $this->state(fn (): array => ['status' => $status, 'awaiting_since' => CarbonImmutable::now()]),
        };
    }

    private function closed(DepositStatus $status, int $retainedCents): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'retained' => Money::fromStored($retainedCents),
            'refunded' => Money::fromStored($attributes['amount']->minorUnits - $retainedCents),
            'closed_by' => fn () => Factory::factoryForModel($this->userModel()),
            'closed_agency_id' => Agency::factory(),
            'closed_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
