<?php

namespace Functional\Booking\States;

use Functional\Booking\Exceptions\IllegalReservationTransitionException;

trait RefusesReservationTransitions
{
    public function depart(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, 'depart');
    }

    public function returnMachine(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, 'return');
    }

    public function cancel(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, 'cancel');
    }
}
