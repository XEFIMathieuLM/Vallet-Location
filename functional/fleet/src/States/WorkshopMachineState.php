<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

final class WorkshopMachineState implements MachineState
{
    use RefusesMachineTransitions;

    public function status(): MachineStatus
    {
        return MachineStatus::Workshop;
    }

    public function acceptsReservations(): bool
    {
        return false;
    }

    public function makeAvailable(): MachineState
    {
        return new AvailableMachineState();
    }

    public function markOutOfOrder(): MachineState
    {
        return new OutOfOrderMachineState();
    }

    public function retire(): MachineState
    {
        return new RetiredMachineState();
    }
}
