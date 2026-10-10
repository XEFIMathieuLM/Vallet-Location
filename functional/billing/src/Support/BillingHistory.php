<?php

namespace Functional\Billing\Support;

use Functional\Booking\Models\Reservation;

final class BillingHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Reservation $reservation, string $event, array $details = []): void
    {
        activity('billing')
            ->performedOn($reservation)
            ->event($event)
            ->withProperties($details)
            ->log(__("billing::history.{$event}", array_map(fn (string|int|null $detail): string => (string) $detail, $details)));
    }
}
