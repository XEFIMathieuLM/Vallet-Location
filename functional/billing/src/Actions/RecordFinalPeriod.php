<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Periods\DateRange;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class RecordFinalPeriod
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly RecordBillablePeriod $recordBillablePeriod,
    ) {}

    public function handle(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== ReservationStatus::Closed || $lockedReservation->returned_at === null) {
                return;
            }

            $returnDate = $this->billingCalendar->dateOf($lockedReservation->returned_at);

            if ($returnDate->lt($this->billingCalendar->goLiveDate()) || $this->hasFinalPeriod($lockedReservation)) {
                return;
            }

            $transmission = $this->recordBillablePeriod->handle(
                $lockedReservation,
                BillablePeriodKind::Final,
                new DateRange($this->recordBillablePeriod->firstUncoveredDate($lockedReservation), $returnDate),
            );

            SendTransmissionJob::dispatch($transmission->id);
        });
    }

    private function hasFinalPeriod(Reservation $reservation): bool
    {
        return BillablePeriod::query()
            ->whereBelongsTo($reservation)
            ->where('kind', BillablePeriodKind::Final)
            ->exists();
    }
}
