<?php

namespace Functional\Fleet\Exceptions;

use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\States\MachineState;

final class IllegalMachineTransitionException extends RefusalException
{
    public static function for(MachineState $from, MachineTransition $transition): self
    {
        return new self(
            "Machine transition {$transition->value} is not allowed from status {$from->status()->value}.",
            'fleet::machines.refusals.illegal_transition',
            ['transition' => $transition, 'status' => $from->status()],
        );
    }
}
