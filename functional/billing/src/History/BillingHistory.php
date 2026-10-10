<?php

namespace Functional\Billing\History;

use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Booking\Models\Reservation;

final class BillingHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Reservation $reservation, BillingHistoryEvent $event, array $details = []): void
    {
        activity('billing')
            ->performedOn($reservation)
            ->event($event->value)
            ->withProperties($details)
            ->log($event->description($details));
    }
}
