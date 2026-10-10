<?php

namespace Functional\Portal\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationRequest>
 */
class ReservationRequestFactory extends Factory
{
    protected $model = ReservationRequest::class;

    public function definition(): array
    {
        $startDate = CarbonImmutable::today()->addDays(faker()->number(3, 20));

        return [
            'customer_account_id' => CustomerAccount::factory(),
            'machine_id' => Machine::factory(),
            'start_date' => $startDate,
            'end_date' => $startDate->addDays(faker()->number(0, 6)),
            'comment' => null,
            'indicative_daily_price_cents' => null,
            'status' => ReservationRequestStatus::Pending,
        ];
    }

    public function confirmed(?Reservation $reservation = null): static
    {
        return $this->state(fn (): array => [
            'status' => ReservationRequestStatus::Confirmed,
            'reservation_id' => $reservation->id ?? Reservation::factory(),
            'decided_at' => now(),
        ]);
    }

    public function refused(string $reason = 'Machine indisponible à ces dates'): static
    {
        return $this->state(fn (): array => [
            'status' => ReservationRequestStatus::Refused,
            'refusal_reason' => $reason,
            'decided_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => ReservationRequestStatus::Cancelled, 'decided_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['status' => ReservationRequestStatus::Expired, 'decided_at' => now()]);
    }
}
