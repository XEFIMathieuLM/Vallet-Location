<?php

namespace Functional\Fleet\Exceptions;

use Functional\Fleet\States\MachineState;

final class IllegalMachineTransitionException extends RefusalException
{
    public static function for(MachineState $from, string $transition): self
    {
        return new self(__('fleet::machines.refusals.illegal_transition', [
            'transition' => __("fleet::machines.transitions.{$transition}"),
            'status' => $from->status()->label(),
        ]));
    }
}
