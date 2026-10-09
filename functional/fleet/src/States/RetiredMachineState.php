<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

final class RetiredMachineState implements MachineState
{
    use RefusesMachineTransitions;

    public function status(): MachineStatus
    {
        return MachineStatus::Retired;
    }

    public function acceptsReservations(): bool
    {
        return false;
    }
}
