<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\InvalidDamageSettlementException;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Models\DamageSettlement;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class WaiveDamage
{
    public function __construct(
        private readonly SettleDamage $settleDamage,
        private readonly BillingHistory $billingHistory,
    ) {}

    public function handle(Damage $damage, string $waiverReason, Model&AgencyMember $settler): DamageSettlement
    {
        if (trim($waiverReason) === '') {
            throw InvalidDamageSettlementException::waiverReasonMissing();
        }

        return DB::transaction(function () use ($damage, $waiverReason, $settler): DamageSettlement {
            $damageSettlement = $this->settleDamage->handle($damage, DamageOutcome::Waived, ['waiver_reason' => trim($waiverReason)], $settler);

            $this->billingHistory->record($damage->reservation, BillingHistoryEvent::DamageWaived, ['damage' => $damage->id, 'reason' => $damageSettlement->waiver_reason]);

            return $damageSettlement;
        });
    }
}
