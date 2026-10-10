<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Models\Machine;
use Illuminate\Support\Facades\DB;

final class RetireMachine
{
    public function __construct(
        private readonly MachineRetirementGuard $retirementGuard,
        private readonly ChangeMachineStatus $changeMachineStatus,
    ) {}

    public function handle(Machine $machine): Machine
    {
        return DB::transaction(function () use ($machine): Machine {
            $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($machine->id);

            $this->retirementGuard->ensureCanRetire($lockedMachine);
            $this->changeMachineStatus->handle($lockedMachine, MachineTransition::Retire);

            return $machine->refresh();
        });
    }
}
