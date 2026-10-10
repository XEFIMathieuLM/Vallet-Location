<?php

namespace Functional\Sales\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class HandoverConflictsWithReservationException extends RefusalException
{
    public static function with(Reservation $reservation, CarbonImmutable $plannedHandoverDate): self
    {
        return new self(
            "Reservation {$reservation->id} ends on or after the planned handover date {$plannedHandoverDate->toDateString()}.",
            'sales::refusals.handover_conflicts_with_reservation',
            [
                'date' => $plannedHandoverDate->format('d/m/Y'),
                'start' => $reservation->start_date->format('d/m/Y'),
                'end' => $reservation->end_date->format('d/m/Y'),
                'agency' => $reservation->agency->name,
                'customer' => $reservation->customer->name,
            ],
        );
    }
}
