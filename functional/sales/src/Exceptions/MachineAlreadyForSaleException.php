<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Models\Sale;

final class MachineAlreadyForSaleException extends RefusalException
{
    public static function withOpenSale(Sale $openSale): self
    {
        return new self(
            "Machine {$openSale->machine_id} already has the open sale {$openSale->id}.",
            'sales::refusals.machine_already_for_sale',
            [
                'price' => $openSale->asking_price->format(),
                'agency' => $openSale->agency->name,
                'date' => $openSale->created_at->format('d/m/Y'),
                'status' => $openSale->status,
            ],
        );
    }

    public static function concurrent(): self
    {
        return new self('Sale rejected by the unique index: another sale of the machine was opened concurrently.', 'sales::refusals.concurrent_listing');
    }
}
