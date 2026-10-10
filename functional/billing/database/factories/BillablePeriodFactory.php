<?php

namespace Functional\Billing\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Models\BillablePeriod;
use Functional\Booking\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillablePeriod>
 */
class BillablePeriodFactory extends Factory
{
    protected $model = BillablePeriod::class;

    public function definition(): array
    {
        $startDate = CarbonImmutable::today()->subDays(faker()->number(5, 30));
        $endDate = $startDate->addDays(faker()->number(0, 4));

        return [
            'reservation_id' => Reservation::factory(),
            'kind' => BillablePeriodKind::Final,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => (int) $startDate->diffInDays($endDate) + 1,
        ];
    }

    public function between(CarbonImmutable $startDate, CarbonImmutable $endDate, BillablePeriodKind $kind): static
    {
        return $this->state(fn (): array => [
            'kind' => $kind,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => (int) $startDate->diffInDays($endDate) + 1,
        ]);
    }
}
