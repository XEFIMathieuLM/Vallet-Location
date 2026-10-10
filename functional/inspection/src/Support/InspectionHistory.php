<?php

namespace Functional\Inspection\Support;

use App\Models\User;
use Functional\Booking\Models\Reservation;

class InspectionHistory
{
    public const LOG_NAME = 'inspection';

    /**
     * @param  array<string, scalar|null>  $details
     */
    public function record(Reservation $reservation, string $event, ?User $author, array $details = []): void
    {
        activity(self::LOG_NAME)
            ->performedOn($reservation)
            ->causedBy($author)
            ->event($event)
            ->withProperties($details)
            ->log($event);
    }
}
