<?php

namespace Functional\Portal\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class RequestedMachineUnavailableException extends RefusalException
{
    public static function for(Machine $machine, CarbonImmutable $startDate, CarbonImmutable $endDate): self
    {
        return new self(
            "Machine {$machine->reference} is not available from {$startDate->toDateString()} to {$endDate->toDateString()}.",
            'portal::refusals.machine_unavailable',
            ['reference' => $machine->reference],
        );
    }
}
