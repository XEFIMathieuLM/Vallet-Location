<?php

namespace Functional\Portal\States;

use Functional\Portal\Enums\ReservationRequestStatus;

final class PendingRequestState implements ReservationRequestState
{
    public function status(): ReservationRequestStatus
    {
        return ReservationRequestStatus::Pending;
    }

    public function isOpen(): bool
    {
        return true;
    }

    public function confirm(): ReservationRequestState
    {
        return new ConfirmedRequestState;
    }

    public function refuse(): ReservationRequestState
    {
        return new RefusedRequestState;
    }

    public function cancel(): ReservationRequestState
    {
        return new CancelledRequestState;
    }

    public function expire(): ReservationRequestState
    {
        return new ExpiredRequestState;
    }
}
