<?php

namespace Functional\Billing\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Support\BillingCalendar;
use Functional\Billing\Support\BillingHistory;
use Functional\Booking\Models\Reservation;

final class RecordBillablePeriod
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly BillingHistory $billingHistory,
    ) {}

    public function firstUncoveredDate(Reservation $reservation): CarbonImmutable
    {
        $lastCoveredDate = BillablePeriod::query()->where('reservation_id', $reservation->id)->max('end_date');

        return is_string($lastCoveredDate)
            ? CarbonImmutable::parse($lastCoveredDate, $this->billingCalendar->today()->timezone)->addDay()
            : $this->billingCalendar->dateOf($reservation->departed_at ?? CarbonImmutable::now());
    }

    public function handle(Reservation $reservation, BillablePeriodKind $kind, CarbonImmutable $startDate, CarbonImmutable $endDate): Transmission
    {
        $billablePeriod = BillablePeriod::query()->create([
            'reservation_id' => $reservation->id,
            'kind' => $kind,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => (int) $startDate->diffInDays($endDate) + 1,
        ]);

        $this->billingHistory->record($reservation, 'period_created', [
            'kind' => $kind->label(),
            'start' => $startDate->format('d/m/Y'),
            'end' => $endDate->format('d/m/Y'),
            'days' => $billablePeriod->days,
        ]);

        return Transmission::query()->create(['billable_period_id' => $billablePeriod->id, 'reservation_id' => $reservation->id]);
    }
}
