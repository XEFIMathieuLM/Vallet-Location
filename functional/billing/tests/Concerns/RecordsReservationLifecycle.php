<?php

namespace Functional\Billing\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Models\BillablePeriod;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;

trait RecordsReservationLifecycle
{
    protected function recordReturn(Reservation $reservation, string $returnedAt): Reservation
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($returnedAt)->setTimezone(config()->string('app.timezone')));
        $reservation->update(['status' => ReservationStatus::Closed, 'returned_at' => CarbonImmutable::now()]);
        ReservationChanged::dispatch($reservation);

        return $reservation->refresh();
    }

    protected function closeMonthsOn(string $moment): void
    {
        CarbonImmutable::setTestNow($moment);
        $this->artisan('billing:close-months')->assertSuccessful();
    }

    /**
     * @return list<array{string, string, string, int}>
     */
    protected function periodsOf(Reservation $reservation): array
    {
        return BillablePeriod::query()
            ->where('reservation_id', $reservation->id)
            ->orderBy('start_date')
            ->get()
            ->map(fn (BillablePeriod $period): array => [
                $period->kind->value,
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
                $period->days,
            ])
            ->all();
    }

    protected function assertSingleFinalPeriod(Reservation $reservation, string $startDate, string $endDate, int $days): void
    {
        $this->assertSame([[BillablePeriodKind::Final->value, $startDate, $endDate, $days]], $this->periodsOf($reservation));
    }
}
