<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Portal\States\ReservationRequestState;

final class IllegalReservationRequestTransitionException extends RefusalException
{
    public static function for(ReservationRequestState $from, string $transition): self
    {
        return new self(
            "Cannot {$transition} a reservation request in status [{$from->status()->value}].",
            'portal::refusals.illegal_transition',
            ['status' => $from->status()],
        );
    }
}
