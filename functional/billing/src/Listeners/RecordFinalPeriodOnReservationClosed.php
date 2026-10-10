<?php

namespace Functional\Billing\Listeners;

use Functional\Billing\Actions\RecordFinalPeriod;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Booking\Events\ReservationChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class RecordFinalPeriodOnReservationClosed implements ShouldQueue, ShouldQueueAfterCommit
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly RecordFinalPeriod $recordFinalPeriod,
    ) {}

    public function handle(ReservationChanged $reservationChanged): void
    {
        if (! $this->billingCalendar->hasGoLiveDate()) {
            return;
        }

        $this->recordFinalPeriod->handle($reservationChanged->reservation);
    }
}
