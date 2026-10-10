<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;

final class ChangeMachineStatus
{
    public function handle(Machine $machine, MachineTransition $transition): Machine
    {
        $machine->update(['status' => $transition->applyTo($machine->state())->status()]);

        MachineChanged::dispatch($machine);

        return $machine;
    }
}
