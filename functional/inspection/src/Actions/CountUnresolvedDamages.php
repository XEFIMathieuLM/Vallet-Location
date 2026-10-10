<?php

namespace Functional\Inspection\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\Damage;

class CountUnresolvedDamages
{
    public function for(Reservation $reservation): int
    {
        return Damage::query()->whereBelongsTo($reservation)->whereNull('resolved_at')->count();
    }
}
