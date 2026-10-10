<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Models\Sale;

final class SaleCancellationRefusedException extends RefusalException
{
    public static function sold(Sale $sale): self
    {
        return new self("Sale {$sale->id} is sold and cannot be cancelled.", 'sales::refusals.sold_sale_not_cancellable');
    }
}
