<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class ConfirmationMachineMismatchException extends RefusalException
{
    public static function for(Machine $chosenMachine, Machine $requestedMachine): self
    {
        return new self(
            "Machine {$chosenMachine->reference} is not of the category of the requested machine {$requestedMachine->reference}.",
            'portal::refusals.machine_mismatch',
            ['reference' => $chosenMachine->reference],
        );
    }
}
