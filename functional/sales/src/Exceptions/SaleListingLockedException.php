<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Models\Sale;

final class SaleListingLockedException extends RefusalException
{
    public static function askingPrice(Sale $sale): self
    {
        return new self(
            "The asking price of sale {$sale->id} cannot change in status {$sale->status->value}.",
            'sales::refusals.asking_price_locked',
            ['status' => $sale->status],
        );
    }

    public static function description(Sale $sale): self
    {
        return new self(
            "The description of sale {$sale->id} cannot change in status {$sale->status->value}.",
            'sales::refusals.description_locked',
            ['status' => $sale->status],
        );
    }
}
