<?php

namespace Functional\Billing\History;

use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Support\Facades\Auth;

final class BillingHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Reservation $reservation, BillingHistoryEvent $event, array $details = []): void
    {
        $author = Auth::user();
        $agencyMember = $author instanceof AgencyMember ? $author : null;

        activity('billing')
            ->performedOn($reservation)
            ->causedBy($agencyMember)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $agencyMember?->agencyId()])
            ->log($event->description($details));
    }
}
