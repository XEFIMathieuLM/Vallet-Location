<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

final class ReservationStateFactory
{
    public static function fromStatus(ReservationStatus $status): ReservationState
    {
        return match ($status) {
            ReservationStatus::Confirmed => new ConfirmedReservationState,
            ReservationStatus::InProgress => new InProgressReservationState,
            ReservationStatus::Closed => new ClosedReservationState,
            ReservationStatus::Cancelled => new CancelledReservationState,
        };
    }
}
