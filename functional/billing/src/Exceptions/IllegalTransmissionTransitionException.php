<?php

namespace Functional\Billing\Exceptions;

use Functional\Billing\States\TransmissionState;

final class IllegalTransmissionTransitionException extends BillingRefusalException
{
    public static function for(TransmissionState $from, string $transition): self
    {
        return new self(
            "Cannot {$transition} a transmission in status [{$from->status()->value}].",
            'billing::transmissions.refusals.illegal_transition',
            ['transition' => __("billing::transmissions.transitions.{$transition}"), 'status' => $from->status()->label()],
        );
    }
}
