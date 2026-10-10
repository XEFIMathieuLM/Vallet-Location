<?php

namespace Functional\Booking\Exceptions;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\States\ReservationState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalReservationTransitionException extends RefusalException
{
    public static function for(ReservationState $from, ReservationTransition $transition): self
    {
        return new self(
            "Reservation transition {$transition->value} is not allowed from status {$from->status()->value}.",
            'booking::reservations.refusals.illegal_transition',
            ['transition' => $transition, 'status' => $from->status()],
        );
    }
}
