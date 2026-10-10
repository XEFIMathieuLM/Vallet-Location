<?php

namespace Functional\Billing\ValueObjects;

use Functional\Billing\Models\DamageSettlement;
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
        public int $billedDamagesTotalCents,
        public Collection $waivedDamages,
        public Collection $unresolvedDamages,
    ) {}
}
