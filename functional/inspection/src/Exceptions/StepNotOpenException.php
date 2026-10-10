<?php

namespace Functional\Inspection\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Enums\InspectionStep;

final class StepNotOpenException extends RefusalException
{
    public static function for(Reservation $reservation, InspectionStep $step): self
    {
        return new self(
            "The {$step->value} photo step is not open for reservation {$reservation->id}.",
            'inspection::photos.refusals.step_not_open',
            ['step' => $step],
        );
    }

    public static function forAnyStep(Reservation $reservation): self
    {
        return new self(
            "No photo step is open for reservation {$reservation->id}.",
            'inspection::photos.refusals.no_open_step',
        );
    }
}
