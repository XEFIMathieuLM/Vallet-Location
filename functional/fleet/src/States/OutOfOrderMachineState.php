<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

final class OutOfOrderMachineState implements MachineState
{
    use RefusesMachineTransitions;

    public function status(): MachineStatus
    {
        return MachineStatus::OutOfOrder;
    }

    public function acceptsReservations(): bool
    {
        return false;
    }

    public function makeAvailable(): MachineState
    {
        return new AvailableMachineState();
    }

    public function sendToWorkshop(): MachineState
    {
        return new WorkshopMachineState();
    }

    public function retire(): MachineState
    {
        return new RetiredMachineState();
    }
}
