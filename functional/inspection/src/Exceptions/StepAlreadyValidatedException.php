<?php

namespace Functional\Inspection\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Enums\InspectionStep;

final class StepAlreadyValidatedException extends RefusalException
{
    public static function for(Reservation $reservation, InspectionStep $step): self
    {
        return new self(
            "The {$step->value} photos of reservation {$reservation->id} are already validated.",
            'inspection::photos.refusals.step_validated',
            ['step' => $step],
        );
    }
}
