<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

final class InProgressReservationState implements ReservationState
{
    use RefusesReservationTransitions;

    public function status(): ReservationStatus
    {
        return ReservationStatus::InProgress;
    }

    public function returnMachine(): ReservationState
    {
        return new ClosedReservationState;
    }
}
