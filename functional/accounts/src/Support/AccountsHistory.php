<?php

namespace Functional\Accounts\Support;

use Functional\Accounts\Enums\AccountsHistoryEvent;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class AccountsHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Model $subject, AccountsHistoryEvent $event, Authenticatable&AgencyMember $author, array $details = []): void
    {
        activity('accounts')
            ->performedOn($subject)
            ->causedBy($author instanceof Model ? $author : null)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $author->agencyId()])
            ->log($event->description($details));
    }
}
