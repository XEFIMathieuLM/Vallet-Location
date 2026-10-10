<?php

namespace Functional\Booking\Exceptions;

use Functional\Booking\States\ReservationState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalReservationTransitionException extends RefusalException
{
    public static function for(ReservationState $from, string $transition): self
    {
        return new self(__('booking::reservations.refusals.illegal_transition', [
            'transition' => __("booking::reservations.transitions.{$transition}"),
            'status' => $from->status()->label(),
        ]));
    }
}
