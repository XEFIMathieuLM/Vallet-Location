<?php

namespace Functional\Booking\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;

final class InvalidReservationDatesException extends RefusalException
{
    public static function startInThePast(CarbonImmutable $startDate): self
    {
        return new self(
            "Reservation start date {$startDate->toDateString()} is in the past.",
            'booking::reservations.refusals.start_in_the_past',
        );
    }

    public static function endBeforeStart(CarbonImmutable $startDate, CarbonImmutable $endDate): self
    {
        return new self(
            "Reservation end date {$endDate->toDateString()} is before its start date {$startDate->toDateString()}.",
            'booking::reservations.refusals.end_before_start',
        );
    }
}
