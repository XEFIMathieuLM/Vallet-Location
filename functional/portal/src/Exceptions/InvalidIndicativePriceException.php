<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidIndicativePriceException extends RefusalException
{
    public static function of(string $typedAmount): self
    {
        return new self("Indicative price [{$typedAmount}] is not a strictly positive amount in euros.", 'portal::refusals.invalid_price');
    }
}
