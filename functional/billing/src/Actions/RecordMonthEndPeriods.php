<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Support\BillingCalendar;
use Functional\Billing\Support\PeriodSplitter;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class RecordMonthEndPeriods
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly PeriodSplitter $periodSplitter,
        private readonly RecordBillablePeriod $recordBillablePeriod,
    ) {}

    public function handle(Reservation $reservation): void
    {
        if (! $this->billingCalendar->isLive()) {
            return;
        }

        DB::transaction(function () use ($reservation): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== ReservationStatus::InProgress) {
                return;
            }

            $elapsedPeriods = $this->periodSplitter->intermediatePeriods(
                $this->recordBillablePeriod->firstUncoveredDate($lockedReservation),
                $this->billingCalendar->today(),
            );

            foreach ($elapsedPeriods as [$startDate, $endDate]) {
                $transmission = $this->recordBillablePeriod->handle($lockedReservation, BillablePeriodKind::Intermediate, $startDate, $endDate);
                SendTransmissionJob::dispatch($transmission->id);
            }
        });
    }
}
