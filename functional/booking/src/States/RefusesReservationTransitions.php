<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Exceptions\IllegalReservationTransitionException;

trait RefusesReservationTransitions
{
    public function depart(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, ReservationTransition::Departure);
    }

    public function returnMachine(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, ReservationTransition::Return);
    }

    public function cancel(): ReservationState
    {
        throw IllegalReservationTransitionException::for($this, ReservationTransition::Cancellation);
    }
}
