<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;

final class AvailableMachineState implements MachineState
{
    use RefusesMachineTransitions;

    public function status(): MachineStatus
    {
        return MachineStatus::Available;
    }

    public function allowedTransitions(): array
    {
        return [MachineTransition::Depart, MachineTransition::SendToWorkshop, MachineTransition::MarkOutOfOrder, MachineTransition::Retire];
    }

    public function acceptsReservations(): bool
    {
        return true;
    }

    public function depart(): MachineState
    {
        return new RentedOutMachineState;
    }

    public function sendToWorkshop(): MachineState
    {
        return new WorkshopMachineState;
    }

    public function markOutOfOrder(): MachineState
    {
        return new OutOfOrderMachineState;
    }

    public function retire(): MachineState
    {
        return new RetiredMachineState;
    }
}
