<?php

namespace Functional\Fleet\Activity;

use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

trait RecordsAuthorAgency
{
    private const AUTHOR_AGENCY_PROPERTY = 'author_agency_id';

    public function beforeActivityLogged(Model $activity, string $eventName): void
    {
        $author = Auth::user();

        if (! $activity instanceof Activity || ! $author instanceof AgencyMember) {
            return;
        }

        $activity->properties = collect($activity->properties)->put(self::AUTHOR_AGENCY_PROPERTY, $author->agencyId());
    }
}
