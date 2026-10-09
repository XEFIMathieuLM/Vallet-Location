<?php

namespace Functional\Booking\Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'created_by' => User::factory(),
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
}
