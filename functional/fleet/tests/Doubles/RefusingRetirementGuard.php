<?php

namespace Functional\Fleet\Tests\Doubles;

use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Fleet\Models\Machine;

final class RefusingRetirementGuard implements MachineRetirementGuard
{
    public function ensureCanRetire(Machine $machine): void
    {
        throw MachineRetirementRefusedException::becauseOfActiveReservations($machine, 1);
    }
}
