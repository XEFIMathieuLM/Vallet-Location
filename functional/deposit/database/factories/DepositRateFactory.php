<?php

namespace Functional\Deposit\Database\Factories;

use Functional\Billing\Money\Money;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositRate>
 */
class DepositRateFactory extends Factory
{
    protected $model = DepositRate::class;

    public function definition(): array
    {
        return [
            'machine_category_id' => MachineCategory::factory(),
            'amount' => Money::fromStored(faker()->number(5, 60) * 10000),
        ];
    }

    public function forCategory(MachineCategory $category): static
    {
        return $this->state(fn (): array => ['machine_category_id' => $category->id]);
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['machine_category_id' => null]);
    }
}
