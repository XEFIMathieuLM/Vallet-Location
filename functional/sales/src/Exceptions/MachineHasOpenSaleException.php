<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Models\Sale;

final class MachineHasOpenSaleException extends RefusalException
{
    public static function for(Machine $machine, Sale $openSale): self
    {
        return new self(
            "Machine {$machine->reference} cannot be retired: it has the open sale {$openSale->id}.",
            'sales::refusals.machine_has_open_sale',
            ['reference' => $machine->reference, 'status' => $openSale->status],
        );
    }
}
