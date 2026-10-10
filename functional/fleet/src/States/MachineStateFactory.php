<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

final class MachineStateFactory
{
    public static function fromStatus(MachineStatus $status): MachineState
    {
        return match ($status) {
            MachineStatus::Available => new AvailableMachineState,
            MachineStatus::RentedOut => new RentedOutMachineState,
            MachineStatus::Workshop => new WorkshopMachineState,
            MachineStatus::OutOfOrder => new OutOfOrderMachineState,
            MachineStatus::Retired => new RetiredMachineState,
        };
    }
}
