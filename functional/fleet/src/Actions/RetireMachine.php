<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Functional\Fleet\Models\Machine;
use Illuminate\Support\Facades\DB;

final class RetireMachine
{
    public function __construct(
        private readonly MachineRetirementGuards $retirementGuards,
        private readonly ChangeMachineStatus $changeMachineStatus,
    ) {}

    public function handle(Machine $machine): Machine
    {
        return DB::transaction(function () use ($machine): Machine {
            $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($machine->id);

            foreach ($this->retirementGuards->all() as $retirementGuard) {
                $retirementGuard->ensureCanRetire($lockedMachine);
            }

            $this->changeMachineStatus->handle($lockedMachine, MachineTransition::Retire);

            return $machine->refresh();
        });
    }
}
