<?php

namespace Functional\Billing\Console;

use Functional\Billing\Actions\RecordMonthEndPeriods;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class CloseMonthsCommand extends Command
{
    protected $signature = 'billing:close-months';

    protected $description = 'Record and transmit the elapsed months of the rentals still running';

    public function handle(BillingCalendar $billingCalendar, RecordMonthEndPeriods $recordMonthEndPeriods): int
    {
        if (! $billingCalendar->isLive()) {
            $this->info("Billing goes live on {$billingCalendar->goLiveDate()->toDateString()}: no period is recorded before.");

            return self::SUCCESS;
        }

        $runningRentalsCount = 0;

        Reservation::query()
            ->where('status', ReservationStatus::InProgress)
            ->chunkById(100, function (Collection $reservations) use ($recordMonthEndPeriods, &$runningRentalsCount): void {
                $reservations->each(function (Reservation $reservation) use ($recordMonthEndPeriods): void {
                    $this->line("Closing elapsed months of reservation #{$reservation->id}.");
                    $recordMonthEndPeriods->handle($reservation);
                });
                $runningRentalsCount += $reservations->count();
            });

        $this->info("Closed elapsed months of {$runningRentalsCount} running rental(s).");

        return self::SUCCESS;
    }
}
