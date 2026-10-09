<?php

namespace Functional\Booking\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidReservationDatesException extends RefusalException
{
    public static function startInThePast(): self
    {
        return new self(__('booking::reservations.refusals.start_in_the_past'));
    }

    public static function endBeforeStart(): self
    {
        return new self(__('booking::reservations.refusals.end_before_start'));
    }
}
