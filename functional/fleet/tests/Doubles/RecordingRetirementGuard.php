<?php

namespace Functional\Fleet\Tests\Doubles;

use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Models\Machine;

final class RecordingRetirementGuard implements MachineRetirementGuard
{
    /**
     * @var list<int>
     */
    public array $checkedMachineIds = [];

    public function ensureCanRetire(Machine $machine): void
    {
        $this->checkedMachineIds[] = $machine->id;
    }
}
