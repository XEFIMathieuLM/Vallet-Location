<?php

namespace Functional\Billing\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\DamageAlreadySettledException;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Model;

final class SettleDamage
{
    public function __construct(private readonly ResolveDamage $resolveDamage) {}

    /**
     * @param  array{amount?: Money, label?: string, waiver_reason?: string}  $outcomeDetails
     */
    public function handle(Damage $damage, DamageOutcome $outcome, array $outcomeDetails, Model&AgencyMember $settler): DamageSettlement
    {
        $lockedDamage = Damage::query()->lockForUpdate()->findOrFail($damage->id);

        if ($lockedDamage->isResolved() || DamageSettlement::query()->whereBelongsTo($lockedDamage)->exists()) {
            throw DamageAlreadySettledException::for($lockedDamage->id);
        }

        $damageSettlement = DamageSettlement::query()->create([
            'damage_id' => $lockedDamage->id,
            'outcome' => $outcome,
            ...$outcomeDetails,
            'settled_by' => $settler->getKey(),
            'settled_at' => CarbonImmutable::now(),
        ]);

        $this->resolveDamage->handle($lockedDamage, $settler);

        return $damageSettlement;
    }
}
