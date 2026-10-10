<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

final class CancelledReservationState implements ReservationState
{
    use RefusesReservationTransitions;

    public function status(): ReservationStatus
    {
        return ReservationStatus::Cancelled;
    }
}
