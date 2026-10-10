<?php

namespace Functional\Billing\Console;

use Functional\Billing\Actions\RecordMonthEndPeriods;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Console\Command;

final class CloseMonthsCommand extends Command
{
    protected $signature = 'billing:close-months';

    protected $description = 'Record and transmit the elapsed months of the rentals still running';

    public function handle(BillingCalendar $billingCalendar, RecordMonthEndPeriods $recordMonthEndPeriods): int
    {
        if (! $billingCalendar->isLive()) {
            $this->info(__('billing::periods.not_live_yet', ['date' => $billingCalendar->goLiveDate()->format('d/m/Y')]));

            return self::SUCCESS;
        }

        Reservation::query()
            ->where('status', ReservationStatus::InProgress)
            ->chunkById(100, fn ($reservations) => $reservations->each(
                fn (Reservation $reservation) => $recordMonthEndPeriods->handle($reservation),
            ));

        return self::SUCCESS;
    }
}
