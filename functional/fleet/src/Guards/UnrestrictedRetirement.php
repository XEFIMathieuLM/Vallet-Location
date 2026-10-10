<?php

namespace Functional\Fleet\Guards;

use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Models\Machine;

final class UnrestrictedRetirement implements MachineRetirementGuard
{
    public function ensureCanRetire(Machine $machine): void {}
}
