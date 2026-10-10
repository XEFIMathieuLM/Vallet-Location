<?php

namespace Functional\Sales\Exceptions;

use DomainException;
use Functional\Sales\Models\Sale;

final class UnsoldSaleTransmissionException extends DomainException
{
    public static function for(Sale $sale): self
    {
        return new self("Sale {$sale->id} has no final price and cannot be transmitted.");
    }
}
