<?php

namespace Functional\Booking\Contracts;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

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
