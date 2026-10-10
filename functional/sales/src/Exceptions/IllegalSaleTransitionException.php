<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Enums\SaleTransition;
use Functional\Sales\States\SaleState;

final class IllegalSaleTransitionException extends RefusalException
{
    public static function for(SaleState $from, SaleTransition $transition): self
    {
        return new self(
            "Sale transition {$transition->value} is not allowed from status {$from->status()->value}.",
            'sales::refusals.illegal_sale_transition',
            ['transition' => $transition, 'status' => $from->status()],
        );
    }
}
