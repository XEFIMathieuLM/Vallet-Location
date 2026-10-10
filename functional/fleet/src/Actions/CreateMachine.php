<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Uniqueness\MachineReferences;
use Illuminate\Support\Facades\DB;

final class CreateMachine
{
    public function __construct(private readonly MachineReferences $machineReferences) {}

    public function handle(MachineAttributes $attributes): Machine
    {
        $category = MachineCategory::query()->findOrFail($attributes->categoryId);

        $machine = $this->machineReferences->writeUnique(
            $attributes->reference,
            null,
            fn (): Machine => DB::transaction(fn (): Machine => Machine::query()->create([
                'reference' => $attributes->reference,
                'machine_category_id' => $category->id,
                'agency_id' => $attributes->agencyId,
                'status' => MachineStatus::Available,
                'is_subject_to_vgp' => $category->is_vgp_required || $attributes->isSubjectToVgp,
                'vgp_due_date' => $attributes->vgpDueDate,
            ])),
        );

        MachineChanged::dispatch($machine);

        return $machine;
    }
}
