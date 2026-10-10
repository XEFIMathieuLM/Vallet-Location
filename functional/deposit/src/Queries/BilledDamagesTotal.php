<?php

namespace Functional\Deposit\Queries;

use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\Damage;

final class BilledDamagesTotal
{
    public function for(Reservation $reservation): Money
    {
        $billedMinorUnits = DamageSettlement::query()
            ->where('outcome', DamageOutcome::Billed->value)
            ->whereIn('damage_id', Damage::query()->select('id')->whereBelongsTo($reservation))
            ->sum('amount_cents');

        return Money::fromStored((int) $billedMinorUnits);
    }
}
