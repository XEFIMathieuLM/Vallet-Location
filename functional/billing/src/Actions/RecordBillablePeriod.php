<?php

namespace Functional\Billing\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Periods\DateRange;
use Functional\Booking\Models\Reservation;

final class RecordBillablePeriod
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly BillingHistory $billingHistory,
    ) {}

    public function firstUncoveredDate(Reservation $reservation): CarbonImmutable
    {
        $lastCoveredDate = BillablePeriod::query()->whereBelongsTo($reservation)->max('end_date');

        return is_string($lastCoveredDate)
            ? CarbonImmutable::parse($lastCoveredDate, $this->billingCalendar->today()->timezone)->addDay()
            : $this->billingCalendar->dateOf($reservation->departed_at ?? CarbonImmutable::now());
    }

    public function handle(Reservation $reservation, BillablePeriodKind $kind, DateRange $dateRange): Transmission
    {
        $billablePeriod = BillablePeriod::query()->create([
            'reservation_id' => $reservation->id,
            'kind' => $kind,
            'start_date' => $dateRange->start,
            'end_date' => $dateRange->end,
            'days' => $dateRange->days(),
        ]);

        $this->billingHistory->record($reservation, BillingHistoryEvent::PeriodCreated, [
            'kind' => $kind->label(),
            'start' => $dateRange->start->format('d/m/Y'),
            'end' => $dateRange->end->format('d/m/Y'),
            'days' => $billablePeriod->days,
        ]);

        return Transmission::query()->create(['billable_period_id' => $billablePeriod->id, 'reservation_id' => $reservation->id]);
    }
}
