<?php

namespace Functional\Fleet\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'reference' => faker()->unique()->machineReference(),
            'machine_category_id' => MachineCategory::factory(),
            'agency_id' => Agency::factory(),
            'status' => MachineStatus::Available,
            'is_subject_to_vgp' => false,
            'vgp_due_date' => null,
        ];
    }

    public function withStatus(MachineStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function subjectToVgpUntil(?CarbonImmutable $dueDate): static
    {
        return $this->state(fn (): array => [
            'is_subject_to_vgp' => true,
            'vgp_due_date' => $dueDate,
        ]);
    }
}
