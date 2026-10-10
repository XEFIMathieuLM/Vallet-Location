<?php

namespace Functional\Booking\Console;

use Functional\Booking\Actions\RefreshReservationConflicts;
use Functional\Booking\Queries\OuterMachineReservations;
use Functional\Fleet\Models\Machine;
use Illuminate\Console\Command;

final class FlagLateReturns extends Command
{
    protected $signature = 'booking:flag-late-returns';

    protected $description = 'Flag the reservations waiting for a machine that has not been returned on time';

    public function handle(RefreshReservationConflicts $refreshReservationConflicts, OuterMachineReservations $outerMachineReservations): int
    {
        $overdueMachines = Machine::query()
            ->whereExists($outerMachineReservations->overdue())
            ->get();

        $refreshReservationConflicts->handleMachines($overdueMachines);

        $this->info("Checked the upcoming reservations of {$overdueMachines->count()} overdue machine(s).");

        return self::SUCCESS;
    }
}
