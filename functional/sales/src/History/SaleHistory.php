<?php

namespace Functional\Sales\History;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;

final class SaleHistory
{
    /**
     * @param  array<string, string|int|null>  $details
     */
    public function record(Sale $sale, Model&AgencyMember $author, SaleHistoryEvent $event, array $details = []): void
    {
        activity('sales')
            ->performedOn($sale)
            ->causedBy($author)
            ->event($event->value)
            ->withProperties([...$details, 'author_agency_id' => $author->agencyId()])
            ->log($event->description($details));
    }
}
