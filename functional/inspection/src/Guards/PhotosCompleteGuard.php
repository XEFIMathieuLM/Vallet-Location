<?php

namespace Functional\Inspection\Guards;

use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\FreezeReservationViews;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\MissingPhotosException;

class PhotosCompleteGuard implements ReservationTransitionGuard
{
    public function __construct(
        private readonly FreezeReservationViews $freezeReservationViews,
        private readonly MissingViews $missingViews,
    ) {}

    public function beforeDeparture(Reservation $reservation): void
    {
        $this->ensureComplete($reservation, InspectionStep::Departure);
    }

    public function beforeReturn(Reservation $reservation): void
    {
        $this->ensureComplete($reservation, InspectionStep::Return);
    }

    private function ensureComplete(Reservation $reservation, InspectionStep $step): void
    {
        $this->freezeReservationViews->handle($reservation);

        $missingViews = $this->missingViews->for($reservation, $step);

        if ($missingViews->isNotEmpty()) {
            throw MissingPhotosException::for($missingViews);
        }
    }
}
