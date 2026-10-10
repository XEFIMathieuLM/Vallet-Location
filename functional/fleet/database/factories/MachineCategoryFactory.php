<?php

namespace Functional\Fleet\Database\Factories;

use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineCategory>
 */
class MachineCategoryFactory extends Factory
{
    protected $model = MachineCategory::class;

    public function definition(): array
    {
        return [
            'name' => faker()->machineCategoryName(),
            'is_vgp_required' => false,
        ];
    }

    public function requiringVgp(): static
    {
        return $this->state(fn (): array => ['is_vgp_required' => true]);
    }
}
