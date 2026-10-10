<?php

namespace Functional\Fleet\Contracts;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

interface MachineRetirementGuard
{
    /**
     * @throws RefusalException
     */
    public function ensureCanRetire(Machine $machine): void;
}
