<?php

namespace Functional\Inspection\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class DamageNotReportableException extends RefusalException
{
    public static function returnPhotosIncomplete(Reservation $reservation): self
    {
        return new self(
            "Damage refused: reservation {$reservation->id} does not have every return photo.",
            'inspection::damages.refusals.return_photos_incomplete',
        );
    }

    public static function emptyComment(): self
    {
        return new self(
            'Damage refused: the comment is empty.',
            'inspection::damages.refusals.empty_comment',
        );
    }
}
