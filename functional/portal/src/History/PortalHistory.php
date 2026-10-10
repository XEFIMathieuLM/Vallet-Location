<?php

namespace Functional\Portal\History;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Portal\Enums\PortalHistoryEvent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class PortalHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Model $subject, PortalHistoryEvent $event, ?Authenticatable $author, array $details = []): void
    {
        $authorAgency = $author instanceof AgencyMember ? ['author_agency_id' => $author->agencyId()] : [];

        activity('portal')
            ->performedOn($subject)
            ->causedBy($author instanceof Model ? $author : null)
            ->event($event->value)
            ->withProperties([...$details, ...$authorAgency, 'is_automatic' => $author === null])
            ->log($event->description($details));
    }
}
