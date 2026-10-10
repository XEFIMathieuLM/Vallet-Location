<?php

namespace Functional\Booking\Console;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\RefreshReservationConflicts;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;

final class FlagLateReturns extends Command
{
    protected $signature = 'booking:flag-late-returns';

    protected $description = 'Flag the reservations waiting for a machine that has not been returned on time';

    public function handle(RefreshReservationConflicts $refreshReservationConflicts): int
    {
        $overdueMachines = Machine::query()
            ->whereExists(fn (Builder $reservations) => $reservations
                ->selectRaw('1')
                ->from('reservations')
                ->whereColumn('reservations.machine_id', 'machines.id')
                ->where('reservations.status', ReservationStatus::InProgress->value)
                ->whereDate('reservations.end_date', '<', CarbonImmutable::today()))
            ->get();

        $refreshReservationConflicts->handleMachines($overdueMachines);

        $this->info(trans_choice('booking::reservations.late_returns.flagged', $overdueMachines->count()));

        return self::SUCCESS;
    }
}
