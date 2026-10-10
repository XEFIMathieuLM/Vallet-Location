<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Models\Reservation;

final class RefusingGuard implements ReservationTransitionGuard
{
    public function beforeDeparture(Reservation $reservation): void
    {
        throw GuardRefusalException::missingPhotos('Photos de départ manquantes.');
    }

    public function beforeReturn(Reservation $reservation): void
    {
        throw GuardRefusalException::missingPhotos('Photos de retour manquantes.');
    }
}
