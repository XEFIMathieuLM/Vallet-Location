<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidSaleListingException extends RefusalException
{
    public static function nonPositivePrice(): self
    {
        return new self('The asking price of a sale must be strictly positive.', 'sales::refusals.non_positive_price');
    }
}
