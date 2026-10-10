<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class MachineAlreadySoldException extends RefusalException
{
    public static function for(Machine $machine): self
    {
        return new self("Machine {$machine->id} has already been sold.", 'sales::refusals.machine_already_sold', ['reference' => $machine->reference]);
    }
}
