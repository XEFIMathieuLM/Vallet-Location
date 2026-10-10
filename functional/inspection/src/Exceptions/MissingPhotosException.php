<?php

namespace Functional\Inspection\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Support\Collection;

final class MissingPhotosException extends RefusalException
{
    /**
     * @param  Collection<int, ReservationView>  $missingViews
     */
    public static function for(Reservation $reservation, InspectionStep $step, Collection $missingViews): self
    {
        return new self(
            "Transition refused: reservation {$reservation->id} lacks {$step->value} photos on {$missingViews->count()} views.",
            'inspection::photos.refusals.missing_photos',
            ['views' => $missingViews->pluck('label')->implode(', ')],
        );
    }
}
