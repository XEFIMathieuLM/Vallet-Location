<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

final class ConfirmedReservationState implements ReservationState
{
    use RefusesReservationTransitions;

    public function status(): ReservationStatus
    {
        return ReservationStatus::Confirmed;
    }

    public function depart(): ReservationState
    {
        return new InProgressReservationState;
    }

    public function cancel(): ReservationState
    {
        return new CancelledReservationState;
    }
}
