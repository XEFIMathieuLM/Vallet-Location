<?php

namespace Functional\Portal\States;

use Functional\Portal\Exceptions\IllegalReservationRequestTransitionException;

trait RefusesRequestTransitions
{
    public function isOpen(): bool
    {
        return false;
    }

    public function confirm(): ReservationRequestState
    {
        throw IllegalReservationRequestTransitionException::for($this, 'confirm');
    }

    public function refuse(): ReservationRequestState
    {
        throw IllegalReservationRequestTransitionException::for($this, 'refuse');
    }

    public function cancel(): ReservationRequestState
    {
        throw IllegalReservationRequestTransitionException::for($this, 'cancel');
    }

    public function expire(): ReservationRequestState
    {
        throw IllegalReservationRequestTransitionException::for($this, 'expire');
    }
}
