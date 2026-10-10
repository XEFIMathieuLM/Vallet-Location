<?php

namespace Functional\Booking\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class ReservationOverlapException extends RefusalException
{
    public static function with(Reservation $conflictingReservation): self
    {
        return new self(__('booking::reservations.refusals.overlap', [
            'start' => $conflictingReservation->start_date->format('d/m/Y'),
            'end' => $conflictingReservation->end_date->format('d/m/Y'),
            'agency' => $conflictingReservation->agency->name,
        ]));
    }

    public static function concurrent(): self
    {
        return new self(__('booking::reservations.refusals.concurrent_overlap'));
    }
}
