<?php

namespace Functional\Portal\History;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Portal\Enums\PortalHistoryEvent;
use Illuminate\Database\Eloquent\Model;

final class PortalHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Model $subject, PortalHistoryEvent $event, ?Model $author, array $details = []): void
    {
        $authorAgency = $author instanceof AgencyMember ? ['author_agency_id' => $author->agencyId()] : [];

        activity('portal')
            ->performedOn($subject)
            ->causedBy($author)
            ->event($event->value)
            ->withProperties([...$details, ...$authorAgency, 'is_automatic' => $author === null])
            ->log($event->description($details));
    }
}
