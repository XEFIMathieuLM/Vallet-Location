<?php

namespace Functional\Portal\States;

use Functional\Portal\Enums\ReservationRequestStatus;

final class ReservationRequestStateFactory
{
    public static function fromStatus(ReservationRequestStatus $status): ReservationRequestState
    {
        return match ($status) {
            ReservationRequestStatus::Pending => new PendingRequestState,
            ReservationRequestStatus::Confirmed => new ConfirmedRequestState,
            ReservationRequestStatus::Refused => new RefusedRequestState,
            ReservationRequestStatus::Cancelled => new CancelledRequestState,
            ReservationRequestStatus::Expired => new ExpiredRequestState,
        };
    }
}
