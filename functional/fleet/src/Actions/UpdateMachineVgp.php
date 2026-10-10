<?php

namespace Functional\Fleet\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;

final class UpdateMachineVgp
{
    public function handle(Machine $machine, ?CarbonImmutable $vgpDueDate): Machine
    {
        $machine->update(['vgp_due_date' => $vgpDueDate]);

        MachineChanged::dispatch($machine);

        return $machine;
    }
}
