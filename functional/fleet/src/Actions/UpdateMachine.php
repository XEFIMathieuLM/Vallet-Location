<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Uniqueness\MachineReferences;
use Illuminate\Support\Facades\DB;

final class UpdateMachine
{
    public function __construct(private readonly MachineReferences $machineReferences) {}

    public function handle(Machine $machine, MachineAttributes $attributes): Machine
    {
        $category = MachineCategory::query()->findOrFail($attributes->categoryId);

        $this->machineReferences->writeUnique(
            $attributes->reference,
            $machine->id,
            fn (): bool => DB::transaction(fn (): bool => $machine->update([
                'reference' => $attributes->reference,
                'machine_category_id' => $category->id,
                'agency_id' => $attributes->agencyId,
                'is_subject_to_vgp' => $category->is_vgp_required || $attributes->isSubjectToVgp,
                'vgp_due_date' => $attributes->vgpDueDate,
            ])),
        );

        MachineChanged::dispatch($machine);

        return $machine;
    }
}
