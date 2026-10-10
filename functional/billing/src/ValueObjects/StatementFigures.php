<?php

namespace Functional\Billing\ValueObjects;

use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Collection;

final readonly class StatementFigures
{
    /**
     * @param  Collection<int, DamageSettlement>  $waivedDamages
     * @param  Collection<int, Damage>  $unresolvedDamages
     */
    public function __construct(
        public int $transmittedRentalsCount,
        public Money $billedDamagesTotal,
        public Collection $waivedDamages,
        public Collection $unresolvedDamages,
    ) {}
}
