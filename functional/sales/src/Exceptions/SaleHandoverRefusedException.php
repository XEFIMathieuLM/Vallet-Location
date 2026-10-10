<?php

namespace Functional\Sales\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;
use Illuminate\Support\Collection;

final class SaleHandoverRefusedException extends RefusalException
{
    public static function machineRentedOut(Reservation $reservation): self
    {
        return new self(
            "Machine {$reservation->machine_id} is rented out by reservation {$reservation->id}.",
            'sales::refusals.handover_machine_rented_out',
            ['start' => $reservation->start_date->format('d/m/Y'), 'end' => $reservation->end_date->format('d/m/Y')],
        );
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     */
    public static function activeReservations(Collection $reservations): self
    {
        return new self(
            "The machine still has {$reservations->count()} confirmed reservation(s).",
            'sales::refusals.handover_active_reservations',
            ['reservations' => $reservations
                ->map(fn (Reservation $reservation): string => "{$reservation->start_date->format('d/m/Y')} – {$reservation->end_date->format('d/m/Y')} ({$reservation->agency->name})")
                ->implode(', ')],
        );
    }
}
