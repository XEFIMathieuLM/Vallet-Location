<?php

namespace Functional\Booking\States;

use Functional\Booking\Enums\ReservationStatus;

interface ReservationState
{
    public function status(): ReservationStatus;

    public function depart(): ReservationState;

    public function returnMachine(): ReservationState;

    public function cancel(): ReservationState;
}
