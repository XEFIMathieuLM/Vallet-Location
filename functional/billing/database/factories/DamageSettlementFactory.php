<?php

namespace Functional\Billing\Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Models\DamageSettlement;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageSettlement>
 */
class DamageSettlementFactory extends Factory
{
    protected $model = DamageSettlement::class;

    public function definition(): array
    {
        return [
            'damage_id' => Damage::factory()->resolved(),
            'outcome' => DamageOutcome::Billed,
            'amount_cents' => faker()->number(1000, 200000),
            'label' => faker()->sentences(1),
            'waiver_reason' => null,
            'settled_by' => User::factory(),
            'settled_at' => CarbonImmutable::now(),
        ];
    }

    public function billed(int $amountCents, string $label): static
    {
        return $this->state(fn (): array => [
            'outcome' => DamageOutcome::Billed,
            'amount_cents' => $amountCents,
            'label' => $label,
            'waiver_reason' => null,
        ]);
    }

    public function waived(string $waiverReason): static
    {
        return $this->state(fn (): array => [
            'outcome' => DamageOutcome::Waived,
            'amount_cents' => null,
            'label' => null,
            'waiver_reason' => $waiverReason,
        ]);
    }
}
