<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

final class ClosedReservationState implements ReservationState
{
    use RefusesReservationTransitions;

    public function status(): ReservationStatus
    {
        return ReservationStatus::Closed;
    }
}
