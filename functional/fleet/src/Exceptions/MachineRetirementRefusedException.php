<?php

namespace Functional\Fleet\Exceptions;

use Functional\Fleet\Models\Machine;

final class MachineRetirementRefusedException extends RefusalException
{
    public static function becauseOfActiveReservations(Machine $machine, int $activeReservationCount): self
    {
        return new self(trans_choice('fleet::machines.refusals.retirement_with_reservations', $activeReservationCount, [
            'reference' => $machine->reference,
        ]));
    }
}
