<?php

namespace Functional\Booking\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $startDate = CarbonImmutable::today()->addDays(faker()->number(1, 30));
        $endDate = $startDate->addDays(faker()->number(0, 7));

        return [
            'machine_id' => Machine::factory(),
            'customer_id' => Customer::factory(),
            'agency_id' => Agency::factory(),
            'created_by' => fn () => Factory::factoryForModel($this->userModel()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'planned_end_date' => $endDate,
            'status' => ReservationStatus::Confirmed,
        ];
    }

    public function between(CarbonImmutable $startDate, CarbonImmutable $endDate): static
    {
        return $this->state(fn (): array => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'planned_end_date' => $endDate,
        ]);
    }

    public function withStatus(ReservationStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function upcoming(): static
    {
        return $this->periodFromToday(faker()->number(5, 8), faker()->number(0, 4));
    }

    public function ongoing(): static
    {
        return $this->periodFromToday(-faker()->number(1, 3), faker()->number(2, 4))->departed();
    }

    public function overdue(): static
    {
        return $this->periodFromToday(-faker()->number(8, 10), faker()->number(5, 6))->departed();
    }

    public function closed(): static
    {
        return $this->periodFromToday(-faker()->number(30, 40), faker()->number(1, 5))
            ->state(fn (array $attributes): array => [
                'status' => ReservationStatus::Closed,
                'departed_at' => $attributes['start_date'],
                'returned_at' => $attributes['end_date'],
            ]);
    }

    public function cancelled(): static
    {
        return $this->upcoming()->withStatus(ReservationStatus::Cancelled);
    }

    private function periodFromToday(int $startOffsetInDays, int $lengthInDays): static
    {
        $startDate = CarbonImmutable::today()->addDays($startOffsetInDays);

        return $this->between($startDate, $startDate->addDays($lengthInDays));
    }

    private function departed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReservationStatus::InProgress,
            'departed_at' => $attributes['start_date'],
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
