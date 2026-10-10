<?php

namespace Functional\Billing\Exceptions;

use Functional\Billing\States\TransmissionState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalTransmissionTransitionException extends RefusalException
{
    public static function for(TransmissionState $from, string $transition): self
    {
        return new self(
            "Cannot {$transition} a transmission in status [{$from->status()->value}].",
            'billing::transmissions.refusals.illegal_transition',
            ['transition' => (string) __("billing::transmissions.transitions.{$transition}"), 'status' => $from->status()],
        );
    }
}
