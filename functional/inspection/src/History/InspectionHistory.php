<?php

namespace Functional\Inspection\History;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;

class InspectionHistory
{
    public const LOG_NAME = 'inspection';

    /**
     * @param  array<string, scalar|null>  $details
     */
    public function record(Reservation $reservation, InspectionHistoryEvent $event, (Model&AgencyMember)|null $author, array $details = []): void
    {
        activity(self::LOG_NAME)
            ->performedOn($reservation)
            ->causedBy($author)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $author?->agencyId()])
            ->log($event->value);
    }
}
