<?php

namespace Functional\Portal\States;

use Functional\Portal\Enums\ReservationRequestStatus;

final class ConfirmedRequestState implements ReservationRequestState
{
    use RefusesRequestTransitions;

    public function status(): ReservationRequestStatus
    {
        return ReservationRequestStatus::Confirmed;
    }
}
