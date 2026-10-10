<?php

namespace Functional\Billing\Exceptions;

use Functional\Billing\States\TransmissionState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalTransmissionTransitionException extends RefusalException
{
    public static function for(TransmissionState $from, string $transition): self
    {
        return new self(__('billing::transmissions.refusals.illegal_transition', [
            'transition' => __("billing::transmissions.transitions.{$transition}"),
            'status' => $from->status()->label(),
        ]));
    }
}
