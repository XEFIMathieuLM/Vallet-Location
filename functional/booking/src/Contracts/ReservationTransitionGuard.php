<?php

namespace Functional\Booking\Contracts;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

/**
 * Lets another layer veto a departure or a return. Both methods run inside the
 * transition's database transaction, before any change, and refuse by throwing
 * a RefusalException subclass, whose message the screens display as is.
 */
interface ReservationTransitionGuard
{
    /**
     * @throws RefusalException
     */
    public function beforeDeparture(Reservation $reservation): void;

    /**
     * @throws RefusalException
     */
    public function beforeReturn(Reservation $reservation): void;
}
