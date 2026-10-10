<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Exceptions\InvalidDamageSettlementException;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Support\BillingHistory;
use Functional\Billing\Support\EuroAmount;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\DB;

final class BillDamage
{
    public function __construct(
        private readonly SettleDamage $settleDamage,
        private readonly BillingHistory $billingHistory,
        private readonly EuroAmount $euroAmount,
    ) {}

    public function handle(Damage $damage, int $amountCents, string $label, User $settler): DamageSettlement
    {
        if ($amountCents <= 0) {
            throw InvalidDamageSettlementException::because('amount_required');
        }

        if (trim($label) === '') {
            throw InvalidDamageSettlementException::because('label_required');
        }

        return DB::transaction(function () use ($damage, $amountCents, $label, $settler): DamageSettlement {
            $damageSettlement = $this->settleDamage->handle($damage, DamageOutcome::Billed, ['amount_cents' => $amountCents, 'label' => trim($label)], $settler);
            $transmission = Transmission::query()->create(['damage_settlement_id' => $damageSettlement->id, 'reservation_id' => $damage->reservation_id]);

            $this->billingHistory->record($damage->reservation, 'damage_billed', [
                'damage' => $damage->id,
                'label' => $damageSettlement->label,
                'amount' => $this->euroAmount->format($amountCents),
            ]);
            SendTransmissionJob::dispatch($transmission->id);

            return $damageSettlement;
        });
    }
}
