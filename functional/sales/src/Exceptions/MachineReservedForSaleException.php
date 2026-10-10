<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Models\Sale;

final class MachineReservedForSaleException extends RefusalException
{
    public static function until(Sale $sale): self
    {
        return new self(
            "Machine {$sale->machine_id} is reserved for sale {$sale->id} with a handover planned on {$sale->planned_handover_date?->toDateString()}.",
            'sales::refusals.machine_reserved_for_sale',
            ['date' => (string) $sale->planned_handover_date?->format('d/m/Y')],
        );
    }
}
