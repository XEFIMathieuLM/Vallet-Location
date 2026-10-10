<?php

namespace Functional\Booking\Listeners;

use Functional\Booking\Actions\RefreshReservationConflicts;
use Functional\Fleet\Events\MachineChanged;

final class RefreshConflictsOnMachineChanged
{
    public function __construct(private readonly RefreshReservationConflicts $refreshReservationConflicts) {}

    public function handle(MachineChanged $event): void
    {
        $this->refreshReservationConflicts->handle($event->machine);
    }
}
