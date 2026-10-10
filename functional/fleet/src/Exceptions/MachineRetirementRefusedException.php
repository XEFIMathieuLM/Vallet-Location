<?php

namespace Functional\Fleet\Exceptions;

use Functional\Fleet\Models\Machine;

final class MachineRetirementRefusedException extends RefusalException
{
    public static function becauseOfActiveReservations(Machine $machine, int $activeReservationCount): self
    {
        return new self(
            "Machine {$machine->reference} cannot be retired: {$activeReservationCount} active reservation(s).",
            'fleet::machines.refusals.retirement_with_reservations',
            ['reference' => $machine->reference],
            $activeReservationCount,
        );
    }
}
