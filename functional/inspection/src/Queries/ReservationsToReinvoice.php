<?php

namespace Functional\Inspection\Queries;

use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Collection;

class ReservationsToReinvoice
{
    /**
     * @return \Illuminate\Support\Collection<int, Collection<int, Damage>>
     */
    public function get(): \Illuminate\Support\Collection
    {
        return Damage::query()
            ->whereNull('resolved_at')
            ->with(['reservation.machine', 'reservation.customer', 'reservation.agency', 'view', 'reporter'])
            ->orderBy('reported_at')
            ->get()
            ->groupBy('reservation_id')
            ->toBase();
    }
}
