<?php

namespace Functional\Billing\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            'amount' => Money::fromStored(faker()->number(1000, 200000)),
            'label' => faker()->sentences(1),
            'waiver_reason' => null,
            'settled_by' => fn () => Factory::factoryForModel($this->userModel()),
            'settled_at' => CarbonImmutable::now(),
        ];
    }

    public function billed(Money $amount, string $label): static
    {
        return $this->state(fn (): array => [
            'outcome' => DamageOutcome::Billed,
            'amount' => $amount,
            'label' => $label,
            'waiver_reason' => null,
        ]);
    }

    public function waived(string $waiverReason): static
    {
        return $this->state(fn (): array => [
            'outcome' => DamageOutcome::Waived,
            'amount' => null,
            'label' => null,
            'waiver_reason' => $waiverReason,
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
