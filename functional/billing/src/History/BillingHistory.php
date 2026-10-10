<?php

namespace Functional\Billing\History;

use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class BillingHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Model $subject, BillingHistoryEvent $event, array $details = []): void
    {
        $author = Auth::user();
        $agencyMember = $author instanceof AgencyMember ? $author : null;

        activity('billing')
            ->performedOn($subject)
            ->causedBy($agencyMember)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $agencyMember?->agencyId()])
            ->log($event->description($details));
    }
}
