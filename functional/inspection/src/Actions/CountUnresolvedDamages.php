<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Models\Damage;

class CountUnresolvedDamages
{
    public function for(int $reservationId): int
    {
        return Damage::query()->where('reservation_id', $reservationId)->whereNull('resolved_at')->count();
    }
}
