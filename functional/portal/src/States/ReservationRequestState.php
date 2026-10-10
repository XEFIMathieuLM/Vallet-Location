<?php

namespace Functional\Portal\States;

use Functional\Portal\Enums\ReservationRequestStatus;

interface ReservationRequestState
{
    public function status(): ReservationRequestStatus;

    public function isOpen(): bool;

    public function confirm(): ReservationRequestState;

    public function refuse(): ReservationRequestState;

    public function cancel(): ReservationRequestState;

    public function expire(): ReservationRequestState;
}
