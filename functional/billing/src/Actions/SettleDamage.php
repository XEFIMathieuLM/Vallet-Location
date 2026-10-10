<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\DamageAlreadySettledException;
use Functional\Billing\Models\DamageSettlement;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Models\Damage;

final class SettleDamage
{
    public function __construct(private readonly ResolveDamage $resolveDamage) {}

    /**
     * @param  array{amount_cents?: int, label?: string, waiver_reason?: string}  $outcomeDetails
     */
    public function handle(Damage $damage, DamageOutcome $outcome, array $outcomeDetails, User $settler): DamageSettlement
    {
        $lockedDamage = Damage::query()->lockForUpdate()->findOrFail($damage->id);

        if ($lockedDamage->isResolved() || DamageSettlement::query()->where('damage_id', $lockedDamage->id)->exists()) {
            throw DamageAlreadySettledException::make();
        }

        $damageSettlement = DamageSettlement::query()->create([
            'damage_id' => $lockedDamage->id,
            'outcome' => $outcome,
            ...$outcomeDetails,
            'settled_by' => $settler->id,
            'settled_at' => CarbonImmutable::now(),
        ]);

        $this->resolveDamage->handle($lockedDamage, $settler);

        return $damageSettlement;
    }
}
