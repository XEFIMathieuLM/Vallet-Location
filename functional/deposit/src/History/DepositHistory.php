<?php

namespace Functional\Deposit\History;

use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;

final class DepositHistory
{
    private const LOG_NAME = 'deposit';

    /**
     * @param  array<string, string|int|bool|null>  $details
     */
    public function record(Reservation $reservation, DepositHistoryEvent $event, Model&AgencyMember $author, array $details = []): void
    {
        activity(self::LOG_NAME)
            ->performedOn($reservation)
            ->causedBy($author)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $author->agencyId()])
            ->log($event->description($details));
    }

    /**
     * @param  array<string, string|int|bool|null>  $details
     */
    public function recordSystem(Reservation $reservation, DepositHistoryEvent $event, array $details = []): void
    {
        activity(self::LOG_NAME)
            ->performedOn($reservation)
            ->event($event->value)
            ->withProperties($details)
            ->log($event->description($details));
    }
}
