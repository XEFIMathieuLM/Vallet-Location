<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\InvalidDamageSettlementException;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\DB;

final class BillDamage
{
    public function __construct(
        private readonly SettleDamage $settleDamage,
        private readonly BillingHistory $billingHistory,
    ) {}

    public function handle(Damage $damage, Money $amount, string $label, User $settler): DamageSettlement
    {
        if (! $amount->isPositive()) {
            throw InvalidDamageSettlementException::because('amount_required');
        }

        if (trim($label) === '') {
            throw InvalidDamageSettlementException::because('label_required');
        }

        return DB::transaction(function () use ($damage, $amount, $label, $settler): DamageSettlement {
            $damageSettlement = $this->settleDamage->handle($damage, DamageOutcome::Billed, ['amount' => $amount, 'label' => trim($label)], $settler);
            $transmission = Transmission::query()->create(['damage_settlement_id' => $damageSettlement->id, 'reservation_id' => $damage->reservation_id]);

            $this->billingHistory->record($damage->reservation, 'damage_billed', [
                'damage' => $damage->id,
                'label' => $damageSettlement->label,
                'amount' => $amount->format(),
            ]);
            SendTransmissionJob::dispatch($transmission->id);

            return $damageSettlement;
        });
    }
}
