<?php

namespace Functional\Portal\States;

use Functional\Portal\Enums\ReservationRequestStatus;

final class RefusedRequestState implements ReservationRequestState
{
    use RefusesRequestTransitions;

    public function status(): ReservationRequestStatus
    {
        return ReservationRequestStatus::Refused;
    }
}
