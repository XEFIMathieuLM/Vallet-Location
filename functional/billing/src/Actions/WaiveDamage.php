<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\InvalidDamageSettlementException;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Models\DamageSettlement;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\DB;

final class WaiveDamage
{
    public function __construct(
        private readonly SettleDamage $settleDamage,
        private readonly BillingHistory $billingHistory,
    ) {}

    public function handle(Damage $damage, string $waiverReason, User $settler): DamageSettlement
    {
        if (trim($waiverReason) === '') {
            throw InvalidDamageSettlementException::waiverReasonMissing();
        }

        return DB::transaction(function () use ($damage, $waiverReason, $settler): DamageSettlement {
            $damageSettlement = $this->settleDamage->handle($damage, DamageOutcome::Waived, ['waiver_reason' => trim($waiverReason)], $settler);

            $this->billingHistory->record($damage->reservation, 'damage_waived', ['damage' => $damage->id, 'reason' => $damageSettlement->waiver_reason]);

            return $damageSettlement;
        });
    }
}
