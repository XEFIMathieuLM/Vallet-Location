<?php

namespace Functional\Fleet\Contracts;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

/**
 * Lets a layer that depends on fleet veto the retirement of a machine, without fleet knowing it.
 */
interface MachineRetirementGuard
{
    /**
     * @throws RefusalException
     */
    public function ensureCanRetire(Machine $machine): void;
}
