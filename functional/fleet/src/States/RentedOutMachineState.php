<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

final class RentedOutMachineState implements MachineState
{
    use RefusesMachineTransitions;

    public function status(): MachineStatus
    {
        return MachineStatus::RentedOut;
    }

    public function acceptsReservations(): bool
    {
        return true;
    }

    public function returnInGoodState(): MachineState
    {
        return new AvailableMachineState();
    }

    public function returnToWorkshop(): MachineState
    {
        return new WorkshopMachineState();
    }
}
