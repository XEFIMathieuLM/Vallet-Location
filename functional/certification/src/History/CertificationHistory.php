<?php

namespace Functional\Certification\History;

use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class CertificationHistory
{
    public const LOG_NAME = 'certification';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Model $subject, CertificationHistoryEvent $event, array $details = []): void
    {
        $author = Auth::user();
        $agencyMember = $author instanceof AgencyMember ? $author : null;

        activity(self::LOG_NAME)
            ->performedOn($subject)
            ->causedBy($agencyMember)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $agencyMember?->agencyId()])
            ->log($event->description($details));
    }
}
